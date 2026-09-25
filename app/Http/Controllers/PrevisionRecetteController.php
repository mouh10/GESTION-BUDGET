<?php

namespace App\Http\Controllers;

use App\Models\Nature;
use App\Models\PrevisionRecette;
use App\Models\Service;
use App\Services\Recettes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Budget des recettes : prévisions. */
class PrevisionRecetteController extends Controller
{
    public function index(Recettes $recettes)
    {
        $exercice = $this->exercice();
        $situation = $recettes->situation($exercice);

        return view('previsions.index', [
            'exercice' => $exercice,
            'situation' => $situation,
            'total' => [
                'prevu' => $situation->sum('prevu'),
                'emis' => $situation->sum('emis'),
                'recouvre' => $situation->sum('recouvre'),
            ],
        ]);
    }

    public function create()
    {
        return view('previsions.form', $this->donnees() + ['prevision' => new PrevisionRecette()]);
    }

    public function store(Request $request)
    {
        $exercice = $this->exercice();
        abort_if($exercice->cloture, 403, 'Exercice clôturé.');
        PrevisionRecette::create($this->valider($request, $exercice->id) + ['exercice_id' => $exercice->id]);

        return redirect()->route('previsions.index')->with('succes', 'Prévision de recette enregistrée.');
    }

    public function edit(PrevisionRecette $prevision)
    {
        return view('previsions.form', $this->donnees() + ['prevision' => $prevision]);
    }

    public function update(Request $request, PrevisionRecette $prevision)
    {
        abort_if($prevision->exercice->cloture, 403, 'Exercice clôturé.');
        $data = $this->valider($request, $prevision->exercice_id, $prevision);
        if ($prevision->titres()->exists()) {
            $data = collect($data)->only(['libelle', 'montant_prevu'])->all();
        }
        $prevision->update($data);

        return redirect()->route('previsions.index')->with('succes', 'Prévision mise à jour.');
    }

    public function destroy(PrevisionRecette $prevision)
    {
        if ($prevision->titres()->exists()) {
            return back()->with('erreur', 'Des titres ont été émis sur cette prévision : elle ne peut pas être supprimée.');
        }
        $prevision->delete();

        return redirect()->route('previsions.index')->with('succes', 'Prévision supprimée.');
    }

    protected function valider(Request $request, int $exerciceId, ?PrevisionRecette $prevision = null): array
    {
        $data = $request->validate([
            'nature_id' => ['required', Rule::exists('natures', 'id')->where('type', 'recette')],
            'service_id' => ['nullable', 'exists:services,id'],
            'libelle' => ['nullable', 'string', 'max:255'],
            'montant_prevu' => ['required', 'numeric', 'min:0'],
        ]);

        $doublon = PrevisionRecette::where('exercice_id', $exerciceId)->where('nature_id', $data['nature_id'])
            ->where('service_id', $data['service_id'] ?? null)
            ->when($prevision, fn ($q) => $q->where('id', '!=', $prevision->id))->exists();
        if ($doublon) {
            throw \Illuminate\Validation\ValidationException::withMessages(['nature_id' => 'Cette prévision existe déjà.']);
        }

        return $data;
    }

    protected function donnees(): array
    {
        return [
            'natures' => Nature::recettes()->where('actif', true)->orderBy('code')->get(),
            'services' => Service::where('actif', true)->orderBy('code')->get(),
        ];
    }
}
