<?php

namespace App\Http\Controllers;

use App\Models\LigneCredit;
use App\Models\Nature;
use App\Models\Programme;
use App\Models\Service;
use App\Services\Credits;
use App\Services\Recettes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** États d'exécution budgétaire. */
class EtatBudgetaireController extends Controller
{
    public function depenses(Request $request, Credits $credits)
    {
        $exercice = $this->exercice();
        $request->validate([
            'par' => ['nullable', Rule::in(['programme', 'action', 'titre', 'service', 'source', 'ligne'])],
            'au' => ['nullable', 'date'],
        ]);
        $par = $request->input('par', 'programme');
        $filtres = $request->only(['programme_id', 'service_id', 'titre', 'source']);
        $situation = $credits->situation($exercice, $filtres, $request->input('au'));

        if ($request->query('export') === 'csv') {
            return $this->csv('execution-depenses-'.$exercice->annee().'.csv', $situation);
        }

        return view('execution.depenses', [
            'exercice' => $exercice,
            'par' => $par,
            'groupes' => $par === 'ligne' ? collect() : $credits->regrouper($situation, $par),
            'situation' => $situation,
            'totaux' => $credits->totaux($situation),
            'programmes' => Programme::orderBy('code')->get(),
            'services' => Service::orderBy('code')->get(),
        ]);
    }

    public function recettes(Recettes $recettes)
    {
        $exercice = $this->exercice();
        $situation = $recettes->situation($exercice);

        return view('execution.recettes', [
            'exercice' => $exercice,
            'situation' => $situation,
            'parCategorie' => $situation->groupBy(fn ($r) => $r->prevision->nature->titre)->sortKeys(),
        ]);
    }

    protected function csv(string $nom, $situation): StreamedResponse
    {
        $n = fn ($v) => number_format((float) $v, 2, ',', '');

        return response()->streamDownload(function () use ($situation, $n) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Programme', 'Action', 'Service', 'Nature', 'Titre', 'Source', 'AE initiales', 'CP initiaux', 'Modif. AE', 'Modif. CP',
                'AE révisées', 'CP révisés', 'AE gelées', 'CP gelés', 'Engagé', 'Liquidé', 'Ordonnancé', 'Payé', 'AE disponibles', 'CP disponibles'], ';');
            foreach ($situation as $r) {
                fputcsv($out, [
                    $r->programme->code, $r->action->code, $r->service->code, $r->nature->code.' '.$r->nature->libelle, $r->titre,
                    LigneCredit::SOURCES[$r->ligne->source] ?? $r->ligne->source,
                    $n($r->ae_initiale), $n($r->cp_initial), $n($r->ae_modif), $n($r->cp_modif), $n($r->ae_revisee), $n($r->cp_revise),
                    $n($r->ae_gelee), $n($r->cp_gele), $n($r->engage), $n($r->liquide), $n($r->ordonnance), $n($r->paye),
                    $n($r->ae_disponible), $n($r->cp_disponible),
                ], ';');
            }
            fclose($out);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
