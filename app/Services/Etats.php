<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Exercice;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * États comptables : balance, grand livre, compte de résultat, bilan.
 * Seules les écritures validées sont prises en compte.
 */
class Etats
{
    protected function base(Exercice $exercice, ?string $du = null, ?string $au = null): Builder
    {
        return DB::table('lignes_ecriture as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->join('comptes as c', 'c.id', '=', 'l.compte_id')
            ->where('e.exercice_id', $exercice->id)
            ->where('e.statut', 'validee')
            ->when($du, fn ($q) => $q->whereDate('e.date', '>=', $du))
            ->when($au, fn ($q) => $q->whereDate('e.date', '<=', $au));
    }

    /**
     * Totaux débit / crédit par compte.
     *
     * @return Collection<int, object{id:int, numero:string, libelle:string, classe:int, debit:float, credit:float, solde:float}>
     */
    public function soldes(Exercice $exercice, ?string $du = null, ?string $au = null): Collection
    {
        return $this->base($exercice, $du, $au)
            ->groupBy('c.id', 'c.numero', 'c.libelle', 'c.classe')
            ->orderBy('c.numero')
            ->selectRaw('c.id, c.numero, c.libelle, c.classe, SUM(l.debit) as debit, SUM(l.credit) as credit')
            ->get()
            ->map(function ($r) {
                $r->debit = round((float) $r->debit, 2);
                $r->credit = round((float) $r->credit, 2);
                $r->solde = round($r->debit - $r->credit, 2);
                $r->classe = (int) $r->classe;

                return $r;
            });
    }

    /* ---------------------------- Balance ---------------------------- */

    public function balance(Exercice $exercice, ?string $du = null, ?string $au = null, ?int $classe = null): array
    {
        $lignes = $this->soldes($exercice, $du, $au)
            ->when($classe, fn ($c) => $c->where('classe', $classe))
            ->values()
            ->map(function ($r) {
                $r->solde_debiteur = $r->solde > 0 ? $r->solde : 0;
                $r->solde_crediteur = $r->solde < 0 ? -$r->solde : 0;

                return $r;
            });

        return [
            'lignes' => $lignes,
            'totaux' => [
                'debit' => round($lignes->sum('debit'), 2),
                'credit' => round($lignes->sum('credit'), 2),
                'solde_debiteur' => round($lignes->sum('solde_debiteur'), 2),
                'solde_crediteur' => round($lignes->sum('solde_crediteur'), 2),
            ],
        ];
    }

    /* -------------------------- Grand livre -------------------------- */

    public function grandLivre(Exercice $exercice, ?string $du = null, ?string $au = null, ?string $compteDu = null, ?string $compteAu = null): Collection
    {
        $filtreComptes = function ($q) use ($compteDu, $compteAu) {
            if ($compteDu) {
                $q->where('c.numero', '>=', $compteDu);
            }
            if ($compteAu) {
                // « 411 » doit inclure 4111, 4112... : on borne par le préfixe suivant.
                $q->where('c.numero', '<', $compteAu.'~');
            }
        };

        $lignes = $this->base($exercice, $du, $au)
            ->where($filtreComptes)
            ->leftJoin('journaux as j', 'j.id', '=', 'e.journal_id')
            ->leftJoin('tiers as t', 't.id', '=', 'l.tiers_id')
            ->orderBy('c.numero')->orderBy('e.date')->orderBy('e.id')->orderBy('l.id')
            ->select([
                'c.id as compte_id', 'c.numero', 'c.libelle as compte_libelle',
                'e.id as ecriture_id', 'e.date', 'e.numero_piece', 'e.libelle as ecriture_libelle',
                'j.code as journal', 'l.libelle', 'l.debit', 'l.credit', 't.nom as tiers',
            ])
            ->get();

        // Soldes antérieurs à la date de début (report).
        $reports = collect();
        if ($du) {
            $reports = $this->base($exercice, null, null)
                ->where($filtreComptes)
                ->whereDate('e.date', '<', $du)
                ->groupBy('c.id', 'c.numero', 'c.libelle')
                ->selectRaw('c.id, c.numero, c.libelle, SUM(l.debit) as debit, SUM(l.credit) as credit')
                ->get()
                ->keyBy('id');
        }

        $comptes = $lignes->groupBy('compte_id');
        $ids = $comptes->keys()->merge($reports->keys())->unique();

        return Compte::whereIn('id', $ids)->orderBy('numero')->get()->map(function ($compte) use ($comptes, $reports) {
            $report = $reports->get($compte->id);
            $reportDebit = round((float) ($report->debit ?? 0), 2);
            $reportCredit = round((float) ($report->credit ?? 0), 2);
            $solde = $reportDebit - $reportCredit;

            $mouvements = ($comptes->get($compte->id) ?? collect())->map(function ($l) use (&$solde) {
                $l->debit = round((float) $l->debit, 2);
                $l->credit = round((float) $l->credit, 2);
                $solde = round($solde + $l->debit - $l->credit, 2);
                $l->solde = $solde;

                return $l;
            });

            return (object) [
                'compte' => $compte,
                'report_debit' => $reportDebit,
                'report_credit' => $reportCredit,
                'mouvements' => $mouvements,
                'total_debit' => round($reportDebit + $mouvements->sum('debit'), 2),
                'total_credit' => round($reportCredit + $mouvements->sum('credit'), 2),
                'solde' => round($solde, 2),
            ];
        });
    }

    /* ----------------------- Compte de résultat ----------------------- */

    /**
     * Compte de résultat simplifié, présentation SYSCOHADA par nature.
     */
    public function compteDeResultat(Exercice $exercice, ?string $du = null, ?string $au = null): array
    {
        $soldes = $this->soldes($exercice, $du, $au)->whereIn('classe', [6, 7, 8]);
        $libelles = Compte::whereRaw('LENGTH(numero) = 2')->pluck('libelle', 'numero');

        $rubriques = [];
        foreach ($soldes as $s) {
            $racine = substr($s->numero, 0, 2);
            $produit = Compte::estProduit($s->numero);
            $montant = $produit ? -$s->solde : $s->solde;
            $rubriques[$racine] ??= ['numero' => $racine, 'libelle' => $libelles[$racine] ?? 'Comptes '.$racine, 'produit' => $produit, 'montant' => 0];
            $rubriques[$racine]['montant'] = round($rubriques[$racine]['montant'] + $montant, 2);
        }
        ksort($rubriques);

        $somme = fn (array $racines) => round(collect($rubriques)->whereIn('numero', $racines)->sum('montant'), 2);

        $produitsExploitation = ['70', '71', '72', '73', '75', '78', '79'];
        $chargesExploitation = ['60', '61', '62', '63', '64', '65', '66', '68', '69'];

        $sections = [
            'exploitation' => [
                'titre' => "Activités d'exploitation",
                'produits' => collect($rubriques)->whereIn('numero', $produitsExploitation)->values(),
                'charges' => collect($rubriques)->whereIn('numero', $chargesExploitation)->values(),
                'resultat' => $somme($produitsExploitation) - $somme($chargesExploitation),
                'libelle_resultat' => "Résultat d'exploitation",
            ],
            'financier' => [
                'titre' => 'Activités financières',
                'produits' => collect($rubriques)->whereIn('numero', ['77'])->values(),
                'charges' => collect($rubriques)->whereIn('numero', ['67'])->values(),
                'resultat' => $somme(['77']) - $somme(['67']),
                'libelle_resultat' => 'Résultat financier',
            ],
            'hao' => [
                'titre' => 'Hors activités ordinaires (HAO)',
                'produits' => collect($rubriques)->whereIn('numero', ['82', '84', '86', '88'])->values(),
                'charges' => collect($rubriques)->whereIn('numero', ['81', '83', '85'])->values(),
                'resultat' => $somme(['82', '84', '86', '88']) - $somme(['81', '83', '85']),
                'libelle_resultat' => 'Résultat HAO',
            ],
        ];

        $resultatActivitesOrdinaires = round($sections['exploitation']['resultat'] + $sections['financier']['resultat'], 2);
        $participation = $somme(['87']);
        $impots = $somme(['89']);

        $totalProduits = round(collect($rubriques)->where('produit', true)->sum('montant'), 2);
        $totalCharges = round(collect($rubriques)->where('produit', false)->sum('montant'), 2);

        return [
            'rubriques' => $rubriques,
            'sections' => $sections,
            'resultat_activites_ordinaires' => $resultatActivitesOrdinaires,
            'participation' => $participation,
            'impots' => $impots,
            'total_produits' => $totalProduits,
            'total_charges' => $totalCharges,
            'resultat_net' => round($totalProduits - $totalCharges, 2),
            'chiffre_affaires' => $somme(['70']),
        ];
    }

    public function resultatNet(Exercice $exercice, ?string $du = null, ?string $au = null): float
    {
        return round(-1 * $this->soldes($exercice, $du, $au)->whereIn('classe', [6, 7, 8])->sum('solde'), 2);
    }

    /* ------------------------------ Bilan ------------------------------ */

    /**
     * Bilan simplifié à une date.
     */
    public function bilan(Exercice $exercice, ?string $au = null): array
    {
        $soldes = $this->soldes($exercice, null, $au);

        $parRacine = function (callable $filtre, bool $inverser = false) use ($soldes) {
            return $soldes->filter($filtre)->groupBy(fn ($s) => substr($s->numero, 0, 2))
                ->map(function ($groupe, $racine) use ($inverser) {
                    $montant = round($groupe->sum('solde'), 2);

                    return (object) [
                        'numero' => (string) $racine,
                        'libelle' => Compte::where('numero', (string) $racine)->value('libelle') ?? 'Comptes '.$racine,
                        'montant' => $inverser ? -$montant : $montant,
                    ];
                })->filter(fn ($r) => abs($r->montant) >= 0.005)->sortKeys()->values();
        };

        // Classe 4 et 5 : ventilation compte par compte selon le sens du solde.
        $debiteur = fn ($s) => $s->solde > 0;
        $crediteur = fn ($s) => $s->solde < 0;

        $actif = [
            'Actif immobilisé' => $parRacine(fn ($s) => $s->classe === 2),
            'Stocks' => $parRacine(fn ($s) => $s->classe === 3),
            'Créances et emplois assimilés' => $parRacine(fn ($s) => $s->classe === 4 && $debiteur($s)),
            'Trésorerie - Actif' => $parRacine(fn ($s) => $s->classe === 5 && $debiteur($s)),
        ];

        $resultat = $this->resultatNet($exercice, null, $au);

        $passif = [
            'Capitaux propres et ressources assimilées' => $parRacine(fn ($s) => $s->classe === 1 && (int) substr($s->numero, 0, 2) <= 15, true),
            'Dettes financières et ressources assimilées' => $parRacine(fn ($s) => $s->classe === 1 && (int) substr($s->numero, 0, 2) >= 16, true),
            'Passif circulant' => $parRacine(fn ($s) => $s->classe === 4 && $crediteur($s), true),
            'Trésorerie - Passif' => $parRacine(fn ($s) => $s->classe === 5 && $crediteur($s), true),
        ];

        $totalActif = round(collect($actif)->flatten(1)->sum('montant'), 2);
        $totalPassif = round(collect($passif)->flatten(1)->sum('montant') + $resultat, 2);

        return compact('actif', 'passif', 'resultat', 'totalActif', 'totalPassif');
    }

    /* --------------------------- Tableau de bord --------------------------- */

    /**
     * Produits et charges par mois sur l'exercice.
     */
    public function evolutionMensuelle(Exercice $exercice): array
    {
        $lignes = $this->base($exercice)
            ->whereIn('c.classe', [6, 7])
            ->groupBy('e.date', 'c.classe')
            ->selectRaw('e.date, c.classe, SUM(l.debit) as debit, SUM(l.credit) as credit')
            ->get();

        $mois = [];
        $curseur = $exercice->date_debut->copy()->startOfMonth();
        while ($curseur <= $exercice->date_fin) {
            $mois[$curseur->format('Y-m')] = ['libelle' => ucfirst($curseur->translatedFormat('M Y')), 'produits' => 0, 'charges' => 0];
            $curseur->addMonth();
        }

        foreach ($lignes as $l) {
            $cle = substr((string) $l->date, 0, 7);
            if (! isset($mois[$cle])) {
                continue;
            }
            if ((int) $l->classe === 7) {
                $mois[$cle]['produits'] += (float) $l->credit - (float) $l->debit;
            } else {
                $mois[$cle]['charges'] += (float) $l->debit - (float) $l->credit;
            }
        }

        return array_values($mois);
    }
}
