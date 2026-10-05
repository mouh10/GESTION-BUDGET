<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompteController extends Controller
{
    public function index(Request $request)
    {
        $comptes = Compte::query()
            ->when($request->filled('classe'), fn ($q) => $q->where('classe', (int) $request->classe))
            ->when($request->filled('q'), function ($q) use ($request) {
                $terme = $request->q;
                $q->where(fn ($w) => $w->where('numero', 'like', $terme.'%')->orWhere('libelle', 'like', '%'.$terme.'%'));
            })
            ->orderBy('numero')
            ->paginate(par_page(50))
            ->withQueryString();

        return view('comptes.index', compact('comptes'));
    }

    public function create(Request $request)
    {
        return view('comptes.form', ['compte' => new Compte(['numero' => $request->query('parent'), 'actif' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero' => ['required', 'regex:/^[1-9][0-9]{1,11}$/', 'unique:comptes,numero'],
            'libelle' => ['required', 'string', 'max:255'],
        ], ['numero.regex' => 'Le numéro doit comporter de 2 à 12 chiffres et commencer par la classe (1 à 9).']);
        $data['actif'] = true;

        $compte = Compte::create($data);

        return redirect()->route('comptes.index', ['q' => substr($compte->numero, 0, 2)])
            ->with('succes', "Compte {$compte->numero} créé.");
    }

    public function edit(Compte $compte)
    {
        return view('comptes.form', compact('compte'));
    }

    public function update(Request $request, Compte $compte)
    {
        $data = $request->validate([
            'numero' => ['required', 'regex:/^[1-9][0-9]{1,11}$/', Rule::unique('comptes', 'numero')->ignore($compte->id)],
            'libelle' => ['required', 'string', 'max:255'],
        ], ['numero.regex' => 'Le numéro doit comporter de 2 à 12 chiffres et commencer par la classe (1 à 9).']);
        $data['actif'] = $request->boolean('actif');

        if ($data['numero'] !== $compte->numero && $compte->estUtilise()) {
            return back()->withInput()->with('erreur', 'Ce compte est déjà utilisé : son numéro ne peut plus être modifié.');
        }

        $compte->update($data);

        return redirect()->route('comptes.index', ['q' => substr($compte->numero, 0, 2)])->with('succes', 'Compte mis à jour.');
    }

    public function destroy(Compte $compte)
    {
        if ($compte->estUtilise()) {
            return back()->with('erreur', 'Ce compte est utilisé : désactivez-le plutôt que de le supprimer.');
        }

        $compte->delete();

        return back()->with('succes', "Compte {$compte->numero} supprimé.");
    }
}
