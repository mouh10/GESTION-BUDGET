<?php

namespace App\Services;

use App\Exceptions\GestionException;
use App\Models\CompteTresorerie;
use App\Models\Engagement;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\LigneCredit;
use App\Models\Liquidation;
use App\Models\Mandat;
use App\Models\MouvementTresorerie;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Chaîne de la dépense publique :
 *   1. Engagement (ordonnateur) → contrôle de la disponibilité des AE
 *   2. Visa du contrôleur financier (ou rejet motivé)
 *   3. Liquidation : service fait, montant dû (≤ reste à liquider)
 *   4. Ordonnancement : émission du mandat → contrôle de la disponibilité des CP
 *   5. Prise en charge par le comptable public (écriture : D charge / C fournisseur) ou rejet
 *   6. Paiement (écriture : D fournisseur / C trésorerie)
 */
class Depenses
{
    public function __construct(protected Credits $credits, protected Comptabilite $compta)
    {
    }

    /* ----------------------------- Engagement ----------------------------- */

    public function enregistrerEngagement(array $data, ?Engagement $engagement = null): Engagement
    {
        $ligne = LigneCredit::with('exercice')->findOrFail($data['ligne_credit_id']);
        $this->verifierExerciceOuvert($ligne->exercice, $data['date']);

        if ($engagement && ! $engagement->estModifiable()) {
            throw new GestionException('Seul un engagement en brouillon ou rejeté peut être modifié.');
        }

        $valeurs = [
            'exercice_id' => $ligne->exercice_id,
            'date' => Carbon::parse($data['date'])->toDateString(),
            'ligne_credit_id' => $ligne->id,
            'tiers_id' => $data['tiers_id'],
            'marche_id' => $data['marche_id'] ?? null,
            'type' => $data['type'],
            'objet' => $data['objet'],
            'montant' => round((float) $data['montant'], 2),
            'statut' => 'brouillon',
            'motif_rejet' => null,
        ];

        if ($engagement) {
            $engagement->update($valeurs);

            return $engagement;
        }

        return Engagement::create($valeurs + [
            'numero' => Numerotation::suivant('EJ-'.$ligne->exercice->annee().'-', 'engagements', 'numero'),
            'user_id' => auth()->id(),
        ]);
    }

    /** Transmission au contrôleur financier après contrôle de la disponibilité des AE. */
    public function soumettre(Engagement $engagement): Engagement
    {
        if (! $engagement->estModifiable()) {
            throw new GestionException('Cet engagement a déjà été soumis.');
        }
        if ((float) $engagement->montant <= 0) {
            throw new GestionException("Le montant de l'engagement doit être supérieur à zéro.");
        }

        $engagement->load('ligneCredit.exercice', 'marche');
        $this->verifierExerciceOuvert($engagement->ligneCredit->exercice, $engagement->date);

        $situation = $this->credits->pourLigne($engagement->ligneCredit);
        if ((float) $engagement->montant - $situation->ae_disponible > 0.004) {
            throw new GestionException('Crédits insuffisants : AE disponibles '.fcfa($situation->ae_disponible)
                .' pour un engagement de '.fcfa($engagement->montant).'. Une modification budgétaire est nécessaire.');
        }

        if ($engagement->marche) {
            $reste = (float) $engagement->marche->montant - $engagement->marche->montantEngage($engagement->id);
            if ((float) $engagement->montant - $reste > 0.004) {
                throw new GestionException('Le montant dépasse le reste à engager sur le marché '.$engagement->marche->numero.' ('.fcfa($reste).').');
            }
            if ($engagement->marche->tiers_id !== $engagement->tiers_id) {
                throw new GestionException("Le bénéficiaire de l'engagement doit être le titulaire du marché.");
            }
        }

        $engagement->update(['statut' => 'soumis', 'soumis_le' => now(), 'motif_rejet' => null]);

        return $engagement;
    }

    public function viser(Engagement $engagement, ?User $viseur = null): Engagement
    {
        if ($engagement->statut !== 'soumis') {
            throw new GestionException("Seul un engagement soumis peut recevoir le visa du contrôle financier.");
        }

        $engagement->update(['statut' => 'vise', 'vise_le' => now(), 'viseur_id' => ($viseur ?? auth()->user())?->id]);

        return $engagement;
    }

    public function rejeter(Engagement $engagement, string $motif): Engagement
    {
        if ($engagement->statut !== 'soumis') {
            throw new GestionException('Seul un engagement soumis peut être rejeté.');
        }
        if (trim($motif) === '') {
            throw new GestionException('Le motif du rejet est obligatoire.');
        }

        $engagement->update(['statut' => 'rejete', 'motif_rejet' => $motif, 'viseur_id' => auth()->id()]);

        return $engagement;
    }

    /** Annulation (libère les AE). Impossible s'il existe une liquidation. */
    public function annulerEngagement(Engagement $engagement): Engagement
    {
        if (in_array($engagement->statut, ['annule'], true)) {
            throw new GestionException('Cet engagement est déjà annulé.');
        }
        if ($engagement->liquidations()->where('statut', 'validee')->exists()) {
            throw new GestionException("L'engagement a déjà été liquidé : annulez d'abord les liquidations.");
        }

        $engagement->update(['statut' => 'annule']);

        return $engagement;
    }

    /* ----------------------------- Liquidation ----------------------------- */

    public function liquider(Engagement $engagement, array $data): Liquidation
    {
        if ($engagement->statut !== 'vise') {
            throw new GestionException("Seul un engagement visé par le contrôle financier peut être liquidé.");
        }

        $montant = round((float) $data['montant'], 2);
        if ($montant <= 0) {
            throw new GestionException('Le montant liquidé doit être supérieur à zéro.');
        }
        if ($montant - $engagement->resteALiquider() > 0.004) {
            throw new GestionException('Le montant dépasse le reste à liquider ('.fcfa($engagement->resteALiquider()).').');
        }

        $engagement->loadMissing('exercice');
        $this->verifierExerciceOuvert($engagement->exercice, $data['date']);

        return Liquidation::create([
            'engagement_id' => $engagement->id,
            'numero' => Numerotation::suivant('LQ-'.$engagement->exercice->annee().'-', 'liquidations', 'numero'),
            'date' => Carbon::parse($data['date'])->toDateString(),
            'reference_facture' => $data['reference_facture'] ?? null,
            'date_service_fait' => $data['date_service_fait'] ?? null,
            'montant' => $montant,
            'observations' => $data['observations'] ?? null,
            'statut' => 'validee',
            'user_id' => auth()->id(),
        ]);
    }

    public function annulerLiquidation(Liquidation $liquidation): Liquidation
    {
        if ($liquidation->statut !== 'validee') {
            throw new GestionException('Cette liquidation est déjà annulée.');
        }
        if ($liquidation->mandatActif()) {
            throw new GestionException('Un mandat est en cours sur cette liquidation : il doit être rejeté par le comptable avant annulation.');
        }

        $liquidation->update(['statut' => 'annulee']);

        return $liquidation;
    }

    /* ---------------------------- Ordonnancement ---------------------------- */

    public function emettreMandat(Liquidation $liquidation, ?string $date = null): Mandat
    {
        if ($liquidation->statut !== 'validee') {
            throw new GestionException('La liquidation est annulée.');
        }
        if ($liquidation->mandatActif()) {
            throw new GestionException('Un mandat a déjà été émis pour cette liquidation.');
        }

        $liquidation->load('engagement.ligneCredit.exercice');
        $engagement = $liquidation->engagement;
        $date = Carbon::parse($date ?? now())->toDateString();
        $this->verifierExerciceOuvert($engagement->ligneCredit->exercice, $date);

        $situation = $this->credits->pourLigne($engagement->ligneCredit);
        if ((float) $liquidation->montant - $situation->cp_disponible > 0.004) {
            throw new GestionException('CP disponibles insuffisants ('.fcfa($situation->cp_disponible).') pour ordonnancer '.fcfa($liquidation->montant).'.');
        }

        return Mandat::create([
            'liquidation_id' => $liquidation->id,
            'numero' => Numerotation::suivant('MD-'.$engagement->ligneCredit->exercice->annee().'-', 'mandats', 'numero'),
            'date' => $date,
            'montant' => $liquidation->montant,
            'statut' => 'emis',
            'user_id' => auth()->id(),
        ]);
    }

    /* ------------------------------ Comptable ------------------------------ */

    /**
     * Prise en charge du mandat par le comptable : écriture au journal des dépenses
     * (débit du compte de la nature économique, crédit du compte du bénéficiaire).
     */
    public function prendreEnCharge(Mandat $mandat, ?string $date = null): Mandat
    {
        if ($mandat->statut !== 'emis') {
            throw new GestionException('Seul un mandat émis peut être pris en charge.');
        }

        $mandat->load('liquidation.engagement.ligneCredit.nature', 'liquidation.engagement.tiers');
        $engagement = $mandat->liquidation->engagement;
        $nature = $engagement->ligneCredit->nature;

        if (! $nature->compte_id) {
            throw new GestionException("La nature {$nature->code} n'a pas d'imputation comptable. Renseignez-la dans Paramètres › Nomenclature économique.");
        }

        return DB::transaction(function () use ($mandat, $engagement, $nature, $date) {
            $libelle = 'Mandat '.$mandat->numero.' - '.$engagement->tiers->nom;
            $montant = (float) $mandat->montant;

            $ecriture = $this->compta->enregistrer([
                'journal_id' => Journal::parCode(config('gestion.journaux.depenses'))->id,
                'date' => Carbon::parse($date ?? now())->max($mandat->date)->toDateString(),
                'libelle' => $libelle,
                'reference' => $mandat->numero,
            ], [
                ['compte_id' => $nature->compte_id, 'libelle' => $engagement->objet, 'debit' => $montant, 'credit' => 0],
                ['compte_id' => $engagement->tiers->compte_id, 'tiers_id' => $engagement->tiers_id, 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant],
            ], true, $mandat);

            $mandat->update(['statut' => 'pris_en_charge', 'pris_en_charge_le' => now(), 'ecriture_id' => $ecriture->id, 'motif_rejet' => null]);

            return $mandat;
        });
    }

    public function rejeterMandat(Mandat $mandat, string $motif): Mandat
    {
        if ($mandat->statut !== 'emis') {
            throw new GestionException('Seul un mandat émis (non encore pris en charge) peut être rejeté.');
        }
        if (trim($motif) === '') {
            throw new GestionException('Le motif du rejet est obligatoire.');
        }

        $mandat->update(['statut' => 'rejete', 'motif_rejet' => $motif]);

        return $mandat;
    }

    /** Paiement du mandat : décaissement de trésorerie au profit du bénéficiaire. */
    public function payer(Mandat $mandat, CompteTresorerie $tresorerie, array $data): MouvementTresorerie
    {
        if ($mandat->statut !== 'pris_en_charge') {
            throw new GestionException('Seul un mandat pris en charge peut être payé.');
        }

        $mandat->load('liquidation.engagement.tiers');
        $engagement = $mandat->liquidation->engagement;

        return DB::transaction(function () use ($mandat, $tresorerie, $data, $engagement) {
            $mouvement = $this->compta->enregistrerMouvement($tresorerie, [
                'date' => $data['date'],
                'type' => 'decaissement',
                'montant' => $mandat->montant,
                'libelle' => 'Paiement mandat '.$mandat->numero.' - '.$engagement->tiers->nom,
                'mode' => $data['mode'] ?? 'virement',
                'reference' => $data['reference'] ?? null,
                'compte_id' => $engagement->tiers->compte_id,
                'tiers_id' => $engagement->tiers_id,
                'mandat_id' => $mandat->id,
            ]);

            $mandat->update(['statut' => 'paye', 'date_paiement' => Carbon::parse($data['date'])->toDateString()]);

            return $mouvement;
        });
    }

    protected function verifierExerciceOuvert(Exercice $exercice, $date): void
    {
        if ($exercice->cloture) {
            throw new GestionException("L'exercice {$exercice->libelle} est clôturé.");
        }
        if (! $exercice->contient($date)) {
            throw new GestionException('La date doit être comprise dans l’exercice '.$exercice->libelle.'.');
        }
    }
}
