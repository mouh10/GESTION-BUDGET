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

    /** Engagements visés et paiements par mois. */
    protected function evolution(Exercice $exercice): array
    {
        $engagements = DB::table('engagements')->where('exercice_id', $exercice->id)->whereIn('statut', ['soumis', 'vise'])
            ->groupBy('date')->selectRaw('date, SUM(montant) as total')->get();
        $paiements = DB::table('mandats as m')->join('liquidations as l', 'l.id', '=', 'm.liquidation_id')
            ->join('engagements as e', 'e.id', '=', 'l.engagement_id')
            ->where('e.exercice_id', $exercice->id)->where('m.statut', 'paye')
            ->groupBy('m.date_paiement')->selectRaw('m.date_paiement as date, SUM(m.montant) as total')->get();

        $mois = [];
        $curseur = $exercice->date_debut->copy()->startOfMonth();
        while ($curseur <= $exercice->date_fin) {
            $mois[$curseur->format('Y-m')] = ['libelle' => ucfirst($curseur->translatedFormat('M')), 'engage' => 0, 'paye' => 0];
            $curseur->addMonth();
        }
        foreach ([['engage', $engagements], ['paye', $paiements]] as [$cle, $lignes]) {
            foreach ($lignes as $l) {
                $k = substr((string) $l->date, 0, 7);
                if (isset($mois[$k])) {
                    $mois[$k][$cle] += (float) $l->total;
                }
            }
        }

        $mois = array_values($mois);

        return [
            'labels' => array_column($mois, 'libelle'),
            'series' => [
                ['label' => 'Engagements', 'data' => array_column($mois, 'engage'), 'couleur' => '#4a90e2'],
                ['label' => 'Paiements', 'data' => array_column($mois, 'paye'), 'couleur' => '#94a3b8'],
            ],
        ];
    }
}
