<?php

namespace App\Services;

use App\Exceptions\GestionException;
use App\Models\CompteTresorerie;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\MouvementTresorerie;
use App\Models\PrevisionRecette;
use App\Models\Tiers;
use App\Models\TitreRecette;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Exécution des recettes : émission des titres (prise en charge : D redevable / C produit)
 * puis recouvrement (D trésorerie / C redevable).
 */
class Recettes
{
    public function __construct(protected Comptabilite $compta)
    {
    }

    public function emettre(array $data): TitreRecette
    {
        $prevision = PrevisionRecette::with('nature', 'exercice')->findOrFail($data['prevision_recette_id']);
        $tiers = Tiers::findOrFail($data['tiers_id']);
        $montant = round((float) $data['montant'], 2);

        if ($montant <= 0) {
            throw new GestionException('Le montant du titre doit être supérieur à zéro.');
        }
        if ($prevision->exercice->cloture || ! $prevision->exercice->contient($data['date'])) {
            throw new GestionException('La date doit être comprise dans un exercice ouvert ('.$prevision->exercice->libelle.').');
        }
        if (! $prevision->nature->compte_id) {
            throw new GestionException("La nature {$prevision->nature->code} n'a pas d'imputation comptable.");
        }

        return DB::transaction(function () use ($data, $prevision, $tiers, $montant) {
            $titre = TitreRecette::create([
                'exercice_id' => $prevision->exercice_id,
                'prevision_recette_id' => $prevision->id,
                'tiers_id' => $tiers->id,
                'numero' => Numerotation::suivant('TR-'.$prevision->exercice->annee().'-', 'titres_recette', 'numero'),
                'date' => Carbon::parse($data['date'])->toDateString(),
                'date_echeance' => $data['date_echeance'] ?? null,
                'objet' => $data['objet'],
                'montant' => $montant,
                'statut' => 'emis',
                'user_id' => auth()->id(),
            ]);

            $libelle = 'Titre '.$titre->numero.' - '.$tiers->nom;
            $ecriture = $this->compta->enregistrer([
                'journal_id' => Journal::parCode(config('gestion.journaux.recettes'))->id,
                'date' => $titre->date->toDateString(),
                'libelle' => $libelle,
                'reference' => $titre->numero,
            ], [
                ['compte_id' => $tiers->compte_id, 'tiers_id' => $tiers->id, 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0],
                ['compte_id' => $prevision->nature->compte_id, 'libelle' => $titre->objet, 'debit' => 0, 'credit' => $montant],
            ], true, $titre);

            $titre->update(['ecriture_id' => $ecriture->id]);

            return $titre;
        });
    }

    public function recouvrer(TitreRecette $titre, CompteTresorerie $tresorerie, array $data): MouvementTresorerie
    {
        if (! $titre->peutEtreRecouvre()) {
            throw new GestionException('Ce titre ne peut plus être recouvré.');
        }

        $montant = round((float) $data['montant'], 2);
        if ($montant <= 0) {
            throw new GestionException('Le montant doit être supérieur à zéro.');
        }
        if ($montant - $titre->reste() > 0.004) {
            throw new GestionException('Le montant dépasse le reste à recouvrer ('.fcfa($titre->reste()).').');
        }

        $titre->loadMissing('tiers');

        return DB::transaction(function () use ($titre, $tresorerie, $data, $montant) {
            $mouvement = $this->compta->enregistrerMouvement($tresorerie, [
                'date' => $data['date'],
                'type' => 'encaissement',
                'montant' => $montant,
                'libelle' => 'Recouvrement '.$titre->numero.' - '.$titre->tiers->nom,
                'mode' => $data['mode'] ?? null,
                'reference' => $data['reference'] ?? null,
                'compte_id' => $titre->tiers->compte_id,
                'tiers_id' => $titre->tiers_id,
                'titre_recette_id' => $titre->id,
            ]);

            $titre->montant_recouvre = round((float) $titre->montant_recouvre + $montant, 2);
            $titre->statut = $titre->reste() <= 0.004 ? 'recouvre' : 'partiellement_recouvre';
            $titre->save();

            return $mouvement;
        });
    }

    public function annuler(TitreRecette $titre): TitreRecette
    {
        if ($titre->statut !== 'emis' || (float) $titre->montant_recouvre > 0) {
            throw new GestionException("Seul un titre sans aucun recouvrement peut être annulé (réduction ou annulation de titre).");
        }

        return DB::transaction(function () use ($titre) {
            if ($titre->ecriture) {
                $this->compta->contrePasser($titre->ecriture, 'Annulation titre '.$titre->numero);
            }
            $titre->update(['statut' => 'annule']);

            return $titre;
        });
    }

    /** Prévu / émis / recouvré par prévision de recette. */
    public function situation(Exercice $exercice): Collection
    {
        $titres = DB::table('titres_recette')
            ->where('exercice_id', $exercice->id)
            ->where('statut', '!=', 'annule')
            ->groupBy('prevision_recette_id')
            ->selectRaw('prevision_recette_id as id, SUM(montant) as emis, SUM(montant_recouvre) as recouvre')
            ->get()->keyBy('id');

        return PrevisionRecette::with('nature', 'service')->where('exercice_id', $exercice->id)->get()
            ->map(function ($p) use ($titres) {
                $t = $titres->get($p->id);
                $prevu = (float) $p->montant_prevu;
                $emis = round((float) ($t->emis ?? 0), 2);
                $recouvre = round((float) ($t->recouvre ?? 0), 2);

                return (object) [
                    'prevision' => $p,
                    'prevu' => $prevu,
                    'emis' => $emis,
                    'recouvre' => $recouvre,
                    'reste_a_recouvrer' => round($emis - $recouvre, 2),
                    'taux_emission' => $prevu > 0 ? round($emis / $prevu * 100, 1) : null,
                    'taux_recouvrement' => $prevu > 0 ? round($recouvre / $prevu * 100, 1) : null,
                ];
            })->sortBy(fn ($r) => $r->prevision->nature->code)->values();
    }
}
