<?php

namespace App\Services;

use App\Exceptions\GestionException;
use App\Models\Compte;
use App\Models\CompteTresorerie;
use App\Models\Ecriture;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\MouvementTresorerie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Moteur de la comptabilité générale : toutes les écritures passent par ce service,
 * qui garantit la partie double (débit = crédit) et le respect des exercices.
 */
class Comptabilite
{
    /* ------------------------------------------------------------------
     |  Écritures
     * ------------------------------------------------------------------ */

    /**
     * @param  array  $entete  journal_id, date, libelle, reference
     * @param  array  $lignes  [compte_id, tiers_id, libelle, debit, credit]
     */
    public function enregistrer(array $entete, array $lignes, bool $valider = true, ?Model $source = null): Ecriture
    {
        [$exercice, $journal, $lignes] = $this->preparer($entete, $lignes);

        return DB::transaction(function () use ($entete, $lignes, $valider, $source, $exercice, $journal) {
            $ecriture = Ecriture::create([
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'numero_piece' => $this->numeroPiece($journal, $exercice),
                'date' => Carbon::parse($entete['date'])->toDateString(),
                'libelle' => $entete['libelle'],
                'reference' => $entete['reference'] ?? null,
                'statut' => $valider ? 'validee' : 'brouillon',
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'user_id' => auth()->id(),
            ]);

            $ecriture->lignes()->createMany($lignes);

            return $ecriture->load('lignes');
        });
    }

    public function modifier(Ecriture $ecriture, array $entete, array $lignes, bool $valider = false): Ecriture
    {
        if ($ecriture->estValidee()) {
            throw new GestionException('Une écriture validée ne peut plus être modifiée. Passez une écriture de contre-passation.');
        }
        if ($ecriture->estAutomatique()) {
            throw new GestionException('Cette écriture a été générée automatiquement et ne peut pas être modifiée ici.');
        }

        [$exercice, $journal, $lignes] = $this->preparer($entete, $lignes);

        return DB::transaction(function () use ($ecriture, $entete, $lignes, $valider, $exercice, $journal) {
            $numero = $ecriture->numero_piece;
            if ($ecriture->journal_id !== $journal->id || $ecriture->exercice_id !== $exercice->id) {
                $numero = $this->numeroPiece($journal, $exercice);
            }

            $ecriture->update([
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'numero_piece' => $numero,
                'date' => Carbon::parse($entete['date'])->toDateString(),
                'libelle' => $entete['libelle'],
                'reference' => $entete['reference'] ?? null,
                'statut' => $valider ? 'validee' : 'brouillon',
            ]);

            $ecriture->lignes()->delete();
            $ecriture->lignes()->createMany($lignes);

            return $ecriture->load('lignes');
        });
    }

    public function valider(Ecriture $ecriture): Ecriture
    {
        if ($ecriture->estValidee()) {
            throw new GestionException('Cette écriture est déjà validée.');
        }
        $ecriture->load('lignes', 'exercice');
        if ($ecriture->exercice->cloture) {
            throw new GestionException("L'exercice {$ecriture->exercice->libelle} est clôturé.");
        }
        if (abs($ecriture->totalDebit() - $ecriture->totalCredit()) > 0.004) {
            throw new GestionException("L'écriture n'est pas équilibrée.");
        }

        $ecriture->update(['statut' => 'validee']);

        return $ecriture;
    }

    public function supprimer(Ecriture $ecriture): void
    {
        if ($ecriture->estValidee()) {
            throw new GestionException('Une écriture validée ne peut pas être supprimée. Passez une écriture de contre-passation.');
        }
        if ($ecriture->estAutomatique()) {
            throw new GestionException('Cette écriture a été générée automatiquement et ne peut pas être supprimée.');
        }

        $ecriture->delete();
    }

    /**
     * Écriture inverse (débit et crédit permutés) qui annule l'effet d'une écriture validée.
     */
    public function contrePasser(Ecriture $ecriture, ?string $libelle = null, ?string $date = null): Ecriture
    {
        $ecriture->load('lignes', 'exercice');

        if (! $date) {
            $date = $ecriture->exercice->cloture ? now()->toDateString() : $ecriture->date->toDateString();
        }

        $lignes = $ecriture->lignes->map(fn ($l) => [
            'compte_id' => $l->compte_id,
            'tiers_id' => $l->tiers_id,
            'libelle' => $l->libelle,
            'debit' => (float) $l->credit,
            'credit' => (float) $l->debit,
        ])->all();

        return $this->enregistrer([
            'journal_id' => $ecriture->journal_id,
            'date' => $date,
            'libelle' => $libelle ?? 'Contre-passation '.$ecriture->numero_piece.' - '.$ecriture->libelle,
            'reference' => $ecriture->numero_piece,
        ], $lignes, true);
    }

    /**
     * Contrôle et nettoie les données d'une écriture.
     *
     * @return array{0: Exercice, 1: Journal, 2: array}
     */
    protected function preparer(array $entete, array $lignes): array
    {
        if (empty($entete['date'])) {
            throw new GestionException("La date de l'écriture est obligatoire.");
        }
        if (empty($entete['libelle'])) {
            throw new GestionException("Le libellé de l'écriture est obligatoire.");
        }

        $date = Carbon::parse($entete['date'])->toDateString();
        $exercice = Exercice::pourDate($date);

        if (! $exercice) {
            throw new GestionException('Aucun exercice comptable ne couvre la date du '.date_fr($date).'.');
        }
        if ($exercice->cloture) {
            throw new GestionException("L'exercice {$exercice->libelle} est clôturé : aucune écriture ne peut y être passée.");
        }

        $journal = Journal::find($entete['journal_id'] ?? null);
        if (! $journal) {
            throw new GestionException('Le journal est obligatoire.');
        }

        $propres = [];
        foreach ($lignes as $ligne) {
            $debit = round((float) str_replace([' ', ','], ['', '.'], (string) ($ligne['debit'] ?? 0)), 2);
            $credit = round((float) str_replace([' ', ','], ['', '.'], (string) ($ligne['credit'] ?? 0)), 2);
            $compteId = $ligne['compte_id'] ?? null;

            if (! $compteId && $debit == 0 && $credit == 0) {
                continue; // ligne vide du formulaire
            }
            if (! $compteId) {
                throw new GestionException('Chaque ligne doit être imputée sur un compte.');
            }
            if ($debit < 0 || $credit < 0) {
                throw new GestionException('Les montants doivent être positifs.');
            }
            if ($debit > 0 && $credit > 0) {
                throw new GestionException('Une ligne ne peut pas être à la fois au débit et au crédit.');
            }
            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $propres[] = [
                'compte_id' => (int) $compteId,
                'tiers_id' => ! empty($ligne['tiers_id']) ? (int) $ligne['tiers_id'] : null,
                'libelle' => $ligne['libelle'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        if (count($propres) < 2) {
            throw new GestionException('Une écriture doit comporter au moins deux lignes (un débit et un crédit).');
        }

        $ids = array_unique(array_column($propres, 'compte_id'));
        if (Compte::whereIn('id', $ids)->count() !== count($ids)) {
            throw new GestionException("Un des comptes utilisés n'existe pas.");
        }

        $totalDebit = round(array_sum(array_column($propres, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($propres, 'credit')), 2);

        if (abs($totalDebit - $totalCredit) > 0.004) {
            throw new GestionException(sprintf(
                "L'écriture n'est pas équilibrée : total débit %s ≠ total crédit %s (écart %s).",
                montant($totalDebit, 2), montant($totalCredit, 2), montant($totalDebit - $totalCredit, 2)
            ));
        }

        return [$exercice, $journal, $propres];
    }

    protected function numeroPiece(Journal $journal, Exercice $exercice): string
    {
        return Numerotation::suivant($journal->code.'-'.$exercice->annee().'-', 'ecritures', 'numero_piece');
    }

    public function compte(string $numero): Compte
    {
        $compte = Compte::parNumero($numero);
        if (! $compte) {
            throw new GestionException("Le compte {$numero} est introuvable dans le plan comptable.");
        }

        return $compte;
    }

    /* ------------------------------------------------------------------
     |  Trésorerie
     * ------------------------------------------------------------------ */

    /**
     * Mouvement libre : encaissement (D trésorerie / C contrepartie)
     * ou décaissement (D contrepartie / C trésorerie).
     */
    public function enregistrerMouvement(CompteTresorerie $tresorerie, array $data): MouvementTresorerie
    {
        $montant = round((float) $data['montant'], 2);
        if ($montant <= 0) {
            throw new GestionException('Le montant doit être supérieur à zéro.');
        }
        if (empty($data['compte_id'])) {
            throw new GestionException('Le compte de contrepartie est obligatoire.');
        }
        if ((int) $data['compte_id'] === (int) $tresorerie->compte_id) {
            throw new GestionException('Le compte de contrepartie doit être différent du compte de trésorerie.');
        }

        return DB::transaction(function () use ($tresorerie, $data, $montant) {
            $encaissement = $data['type'] === 'encaissement';

            $mouvement = MouvementTresorerie::create([
                'compte_tresorerie_id' => $tresorerie->id,
                'date' => $data['date'],
                'type' => $data['type'],
                'montant' => $montant,
                'libelle' => $data['libelle'],
                'mode' => $data['mode'] ?? null,
                'reference' => $data['reference'] ?? null,
                'compte_id' => $data['compte_id'],
                'tiers_id' => $data['tiers_id'] ?? null,
                'mandat_id' => $data['mandat_id'] ?? null,
                'titre_recette_id' => $data['titre_recette_id'] ?? null,
                'virement_id' => $data['virement_id'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $ligneTresorerie = ['compte_id' => $tresorerie->compte_id, 'libelle' => $data['libelle'], 'debit' => $encaissement ? $montant : 0, 'credit' => $encaissement ? 0 : $montant];
            $ligneContrepartie = ['compte_id' => $data['compte_id'], 'tiers_id' => $data['tiers_id'] ?? null, 'libelle' => $data['libelle'], 'debit' => $encaissement ? 0 : $montant, 'credit' => $encaissement ? $montant : 0];

            $ecriture = $this->enregistrer([
                'journal_id' => $tresorerie->journal_id,
                'date' => $data['date'],
                'libelle' => $data['libelle'],
                'reference' => $data['reference'] ?? null,
            ], $encaissement ? [$ligneTresorerie, $ligneContrepartie] : [$ligneContrepartie, $ligneTresorerie], true, $mouvement);

            $mouvement->update(['ecriture_id' => $ecriture->id]);

            return $mouvement;
        });
    }

    /**
     * Virement interne entre deux comptes de trésorerie via le compte 585 (virements de fonds).
     */
    public function virement(CompteTresorerie $source, CompteTresorerie $destination, array $data): string
    {
        if ($source->id === $destination->id) {
            throw new GestionException('Les comptes source et destination doivent être différents.');
        }

        $compteVirement = $this->compte(config('gestion.comptes.virements_internes'));
        $id = (string) Str::uuid();
        $libelle = $data['libelle'] ?? null ?: 'Virement '.$source->nom.' → '.$destination->nom;

        DB::transaction(function () use ($source, $destination, $data, $compteVirement, $id, $libelle) {
            $commun = [
                'date' => $data['date'],
                'montant' => $data['montant'],
                'libelle' => $libelle,
                'mode' => $data['mode'] ?? 'virement',
                'reference' => $data['reference'] ?? null,
                'compte_id' => $compteVirement->id,
                'virement_id' => $id,
            ];
            $this->enregistrerMouvement($source, $commun + ['type' => 'decaissement']);
            $this->enregistrerMouvement($destination, $commun + ['type' => 'encaissement']);
        });

        return $id;
    }

    /**
     * Annule un mouvement : contre-passation de l'écriture, mise à jour du mandat ou du titre lié,
     * puis suppression du mouvement (les deux côtés pour un virement).
     */
    public function annulerMouvement(MouvementTresorerie $mouvement): void
    {
        DB::transaction(function () use ($mouvement) {
            $mouvements = $mouvement->virement_id
                ? MouvementTresorerie::where('virement_id', $mouvement->virement_id)->get()
                : collect([$mouvement]);

            foreach ($mouvements as $m) {
                if ($m->ecriture) {
                    $this->contrePasser($m->ecriture, 'Annulation '.$m->ecriture->numero_piece.' - '.$m->libelle);
                }

                if ($m->mandat) {
                    // Le mandat redevient « pris en charge », en attente de paiement.
                    $m->mandat->update(['statut' => 'pris_en_charge', 'date_paiement' => null]);
                }

                if ($m->titreRecette) {
                    $titre = $m->titreRecette;
                    $titre->montant_recouvre = max(0, round((float) $titre->montant_recouvre - (float) $m->montant, 2));
                    $titre->statut = $titre->montant_recouvre <= 0.004 ? 'emis' : 'partiellement_recouvre';
                    $titre->save();
                }

                $m->delete();
            }
        });
    }

    /**
     * Écriture de solde d'ouverture d'un compte de trésorerie.
     */
    public function ouvertureTresorerie(CompteTresorerie $tresorerie, int $contrepartieId, string $date): ?Ecriture
    {
        $montant = (float) $tresorerie->solde_initial;
        if ($montant == 0.0) {
            return null;
        }

        $libelle = "Solde d'ouverture - ".$tresorerie->nom;
        $positif = $montant > 0;
        $montant = abs($montant);

        return $this->enregistrer([
            'journal_id' => Journal::parCode(config('gestion.journaux.operations_diverses'))->id,
            'date' => $date,
            'libelle' => $libelle,
        ], [
            ['compte_id' => $tresorerie->compte_id, 'libelle' => $libelle, 'debit' => $positif ? $montant : 0, 'credit' => $positif ? 0 : $montant],
            ['compte_id' => $contrepartieId, 'libelle' => $libelle, 'debit' => $positif ? 0 : $montant, 'credit' => $positif ? $montant : 0],
        ], true, $tresorerie);
    }

    /* ------------------------------------------------------------------
     |  Exercices
     * ------------------------------------------------------------------ */

    /**
     * Clôture un exercice. Si un exercice suivant existe, génère les à-nouveaux :
     * soldes des classes 1 à 5 reportés, résultat affecté en 131 (bénéfice) ou 139 (perte).
     */
    public function cloturer(Exercice $exercice, bool $genererANouveaux = true): ?Ecriture
    {
        if ($exercice->cloture) {
            throw new GestionException('Cet exercice est déjà clôturé.');
        }

        $brouillons = $exercice->ecritures()->where('statut', 'brouillon')->count();
        if ($brouillons > 0) {
            throw new GestionException("Impossible de clôturer : {$brouillons} écriture(s) en brouillon. Validez-les ou supprimez-les.");
        }

        return DB::transaction(function () use ($exercice, $genererANouveaux) {
            $ecriture = null;

            if ($genererANouveaux) {
                $suivant = Exercice::whereDate('date_debut', '>', $exercice->date_fin->toDateString())
                    ->orderBy('date_debut')->first();

                if ($suivant && ! $suivant->cloture) {
                    $ecriture = $this->genererANouveaux($exercice, $suivant);
                }
            }

            $exercice->update(['cloture' => true]);

            return $ecriture;
        });
    }

    protected function genererANouveaux(Exercice $exercice, Exercice $suivant): ?Ecriture
    {
        $soldes = DB::table('lignes_ecriture as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->join('comptes as c', 'c.id', '=', 'l.compte_id')
            ->where('e.exercice_id', $exercice->id)
            ->where('e.statut', 'validee')
            ->groupBy('l.compte_id', 'l.tiers_id', 'c.classe')
            ->selectRaw('l.compte_id, l.tiers_id, c.classe, SUM(l.debit) - SUM(l.credit) as solde')
            ->get();

        $lignes = [];
        $resultat = 0.0;
        foreach ($soldes as $s) {
            $solde = round((float) $s->solde, 2);
            if (abs($solde) < 0.005) {
                continue;
            }
            if ($s->classe >= 9) {
                continue; // engagements hors bilan / analytique : pas de report
            }
            if ($s->classe >= 6) {
                $resultat -= $solde; // produits (solde créditeur) - charges (solde débiteur)

                continue;
            }
            $lignes[] = [
                'compte_id' => $s->compte_id,
                'tiers_id' => $s->tiers_id,
                'libelle' => 'À-nouveau',
                'debit' => $solde > 0 ? $solde : 0,
                'credit' => $solde < 0 ? -$solde : 0,
            ];
        }

        $resultat = round($resultat, 2);
        if (abs($resultat) >= 0.005) {
            $compte = $this->compte($resultat > 0 ? '131' : '139');
            $lignes[] = [
                'compte_id' => $compte->id,
                'libelle' => 'Résultat de l\'exercice '.$exercice->libelle,
                'debit' => $resultat < 0 ? -$resultat : 0,
                'credit' => $resultat > 0 ? $resultat : 0,
            ];
        }

        if (count($lignes) < 2) {
            return null;
        }

        $journal = Journal::where('code', 'AN')->first() ?? Journal::parCode(config('gestion.journaux.operations_diverses'));

        return $this->enregistrer([
            'journal_id' => $journal->id,
            'date' => $suivant->date_debut->toDateString(),
            'libelle' => 'À-nouveaux de l\'exercice '.$exercice->libelle,
        ], $lignes, true, $exercice);
    }
}
