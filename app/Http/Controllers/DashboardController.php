<?php

namespace App\Http\Controllers;

use App\Models\Engagement;
use App\Models\Exercice;
use App\Models\Mandat;
use App\Models\Modification;
use App\Models\TitreRecette;
use App\Services\Credits;
use App\Services\Recettes;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Credits $credits, Recettes $recettes)
    {
        $exercice = Exercice::courant();

        if (! $exercice) {
            return view('dashboard', ['exercice' => null]);
        }

        $situation = $credits->situation($exercice);
        $totaux = $credits->totaux($situation);
        $recettesSituation = $recettes->situation($exercice);

        $mandats = fn () => Mandat::whereHas('liquidation.engagement', fn ($q) => $q->where('exercice_id', $exercice->id));

        return view('dashboard', [
            'exercice' => $exercice,
            'totaux' => $totaux,
            'programmes' => $credits->regrouper($situation, 'programme'),
            'titres' => $credits->regrouper($situation, 'titre'),
            'alertes' => $situation->filter(fn ($r) => $r->ae_revisee > 0 && ($r->taux_engagement ?? 0) >= config('gestion.alerte_budget'))
                ->sortByDesc('taux_engagement')->take(6)->values(),
            'recettes' => [
                'prevu' => round($recettesSituation->sum('prevu'), 2),
                'emis' => round($recettesSituation->sum('emis'), 2),
                'recouvre' => round($recettesSituation->sum('recouvre'), 2),
            ],
            'evolution' => $this->evolution($exercice),
            'aTraiter' => [
                'aViser' => Engagement::where('exercice_id', $exercice->id)->where('statut', 'soumis')->selectRaw('COUNT(*) as n, COALESCE(SUM(montant),0) as total')->first(),
                'brouillons' => Engagement::where('exercice_id', $exercice->id)->whereIn('statut', ['brouillon', 'rejete'])->selectRaw('COUNT(*) as n, COALESCE(SUM(montant),0) as total')->first(),
                'aPrendreEnCharge' => $mandats()->where('statut', 'emis')->selectRaw('COUNT(*) as n, COALESCE(SUM(montant),0) as total')->first(),
                'aPayer' => $mandats()->where('statut', 'pris_en_charge')->selectRaw('COUNT(*) as n, COALESCE(SUM(montant),0) as total')->first(),
                'actes' => Modification::where('exercice_id', $exercice->id)->where('statut', 'brouillon')->count(),
                'titres' => TitreRecette::where('exercice_id', $exercice->id)->whereIn('statut', ['emis', 'partiellement_recouvre'])
                    ->selectRaw('COUNT(*) as n, COALESCE(SUM(montant - montant_recouvre),0) as total')->first(),
            ],
            'derniersEngagements' => Engagement::with('tiers', 'ligneCredit.action.programme', 'ligneCredit.nature')
                ->where('exercice_id', $exercice->id)->latest('id')->limit(6)->get(),
        ]);
    }

    /** Engagements, paiements et recouvrements par mois (jusqu'au mois en cours). */
    protected function evolution(Exercice $exercice): array
    {
        // Requêtes directes : la restriction par service est appliquée à la main.
        $ids = \App\Models\Scopes\ParService::servicesAutorises();
        $lignes = fn ($q) => $q->select('id')->from('lignes_credit')->whereIn('service_id', $ids);

        $engagements = DB::table('engagements')->where('exercice_id', $exercice->id)->whereIn('statut', ['soumis', 'vise'])
            ->when($ids, fn ($q) => $q->whereIn('ligne_credit_id', $lignes))
            ->groupBy('date')->selectRaw('date, SUM(montant) as total')->get();
        $recouvrements = DB::table('mouvements_tresorerie as mv')->join('titres_recette as t', 't.id', '=', 'mv.titre_recette_id')
            ->where('t.exercice_id', $exercice->id)->where('mv.type', 'encaissement')
            ->when($ids, fn ($q) => $q->whereIn('t.prevision_recette_id', fn ($p) => $p->select('id')->from('previsions_recette')->whereIn('service_id', $ids)))
            ->groupBy('mv.date')->selectRaw('mv.date as date, SUM(mv.montant) as total')->get();
        $paiements = DB::table('mandats as m')->join('liquidations as l', 'l.id', '=', 'm.liquidation_id')
            ->join('engagements as e', 'e.id', '=', 'l.engagement_id')
            ->where('e.exercice_id', $exercice->id)->where('m.statut', 'paye')
            ->when($ids, fn ($q) => $q->whereIn('e.ligne_credit_id', $lignes))
            ->groupBy('m.date_paiement')->selectRaw('m.date_paiement as date, SUM(m.montant) as total')->get();

        $mois = [];
        $curseur = $exercice->date_debut->copy()->startOfMonth();
        while ($curseur <= $exercice->date_fin) {
            if ($curseur->greaterThan(now()->endOfMonth())) {
                break; // pas de mois futurs sur les courbes
            }
            $mois[$curseur->format('Y-m')] = ['libelle' => ucfirst($curseur->translatedFormat('M')), 'engage' => 0, 'paye' => 0, 'recouvre' => 0];
            $curseur->addMonth();
        }
        foreach ([['engage', $engagements], ['paye', $paiements], ['recouvre', $recouvrements]] as [$cle, $lignes]) {
            foreach ($lignes as $l) {
                $k = substr((string) $l->date, 0, 7);
                if (isset($mois[$k])) {
                    $mois[$k][$cle] += (float) $l->total;
                }
            }
        }

        $mois = array_values($mois);

        $labels = array_column($mois, 'libelle');

        return [
            'engage' => ['labels' => $labels, 'data' => array_column($mois, 'engage')],
            'paye' => ['labels' => $labels, 'data' => array_column($mois, 'paye')],
            'recouvre' => ['labels' => $labels, 'data' => array_column($mois, 'recouvre')],
        ];
    }
}
