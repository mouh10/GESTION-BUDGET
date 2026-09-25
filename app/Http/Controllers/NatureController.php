<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\LigneCredit;
use App\Models\Nature;
use App\Models\PrevisionRecette;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NatureController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type') === 'recette' ? 'recette' : 'depense';

        return view('nomenclature.natures', [
            'type' => $type,
            'natures' => Nature::with('compte')->where('type', $type)->orderBy('titre')->orderBy('code')->get()->groupBy('titre'),
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('type') === 'recette' ? 'recette' : 'depense';

        return view('nomenclature.nature-form', ['nature' => new Nature(['type' => $type, 'actif' => true, 'titre' => $type === 'recette' ? 2 : 3]), 'comptes' => $this->comptes()]);
    }

    public function store(Request $request)
    {
        $nature = Nature::create($this->valider($request) + ['actif' => true]);

        return redirect()->route('natures.index', ['type' => $nature->type])->with('succes', 'Nature créée.');
    }

    public function edit(Nature $nature)
    {
        return view('nomenclature.nature-form', ['nature' => $nature, 'comptes' => $this->comptes()]);
    }

    public function update(Request $request, Nature $nature)
    {
        $nature->update($this->valider($request, $nature) + ['actif' => $request->boolean('actif')]);

        return redirect()->route('natures.index', ['type' => $nature->type])->with('succes', 'Nature mise à jour.');
    }

    public function destroy(Nature $nature)
    {
        if (LigneCredit::where('nature_id', $nature->id)->exists() || PrevisionRecette::where('nature_id', $nature->id)->exists()) {
            return back()->with('erreur', 'Cette nature est utilisée dans le budget : désactivez-la plutôt.');
        }
        $nature->delete();

        return redirect()->route('natures.index', ['type' => $nature->type])->with('succes', 'Nature supprimée.');
    }

    protected function valider(Request $request, ?Nature $nature = null): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(Nature::TYPES))],
            'code' => ['required', 'string', 'max:20', Rule::unique('natures', 'code')->ignore($nature?->id)],
            'libelle' => ['required', 'string', 'max:255'],
            'titre' => ['required', 'integer', 'between:1,9'],
            'compte_id' => ['nullable', 'exists:comptes,id'],
        ]);
    }

    protected function comptes()
    {
        return Compte::saisissables()->whereIn('classe', [2, 6, 7])->get();
    }
}
