<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgrammeController extends Controller
{
    public function index()
    {
        return view('nomenclature.programmes', [
            'programmes' => Programme::with(['actions' => fn ($q) => $q->withCount('lignesCredit')])->orderBy('code')->get(),
        ]);
    }

    public function create()
    {
        return view('nomenclature.programme-form', ['programme' => new Programme(['actif' => true])]);
    }

    public function store(Request $request)
    {
        $programme = Programme::create($this->valider($request) + ['actif' => true]);

        return redirect()->route('programmes.index')->with('succes', "Programme {$programme->code} créé. Ajoutez maintenant ses actions.");
    }

    public function edit(Programme $programme)
    {
        return view('nomenclature.programme-form', compact('programme'));
    }

    public function update(Request $request, Programme $programme)
    {
        $programme->update($this->valider($request, $programme) + ['actif' => $request->boolean('actif')]);

        return redirect()->route('programmes.index')->with('succes', 'Programme mis à jour.');
    }

    public function destroy(Programme $programme)
    {
        if ($programme->lignesCredit()->exists()) {
            return back()->with('erreur', 'Ce programme porte des crédits : désactivez-le plutôt.');
        }
        $programme->delete();

        return redirect()->route('programmes.index')->with('succes', 'Programme supprimé.');
    }

    public function ajouterAction(Request $request, Programme $programme)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('actions', 'code')->where('programme_id', $programme->id)],
            'libelle' => ['required', 'string', 'max:255'],
        ]);
        $programme->actions()->create($data);

        return back()->with('succes', 'Action ajoutée.');
    }

    public function modifierAction(Request $request, Action $action)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('actions', 'code')->where('programme_id', $action->programme_id)->ignore($action->id)],
            'libelle' => ['required', 'string', 'max:255'],
        ]);
        $action->update($data);

        return back()->with('succes', 'Action mise à jour.');
    }

    public function supprimerAction(Action $action)
    {
        if ($action->lignesCredit()->exists()) {
            return back()->with('erreur', 'Cette action porte des crédits : elle ne peut pas être supprimée.');
        }
        $action->delete();

        return back()->with('succes', 'Action supprimée.');
    }

    protected function valider(Request $request, ?Programme $programme = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('programmes', 'code')->ignore($programme?->id)],
            'libelle' => ['required', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'objectif' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
