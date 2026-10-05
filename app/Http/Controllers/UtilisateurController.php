<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UtilisateurController extends Controller
{
    public function index()
    {
        return view('utilisateurs.index', ['utilisateurs' => User::with('service')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('utilisateurs.form', ['utilisateur' => new User(['role' => 'ordonnateur', 'actif' => true, 'doit_changer_mdp' => true]), 'services' => \App\Models\Service::orderBy('code')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'service_id' => ['nullable', 'exists:services,id'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['actif'] = $request->boolean('actif');
        $data['doit_changer_mdp'] = $request->boolean('doit_changer_mdp');

        User::create($data);

        return redirect()->route('utilisateurs.index')->with('succes', 'Utilisateur créé.');
    }

    public function edit(User $utilisateur)
    {
        return view('utilisateurs.form', ['utilisateur' => $utilisateur, 'services' => \App\Models\Service::orderBy('code')->get()]);
    }

    public function update(Request $request, User $utilisateur)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($utilisateur->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'service_id' => ['nullable', 'exists:services,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        $data['actif'] = $request->boolean('actif');
        $data['doit_changer_mdp'] = $request->boolean('doit_changer_mdp');

        if ($utilisateur->is($request->user()) && ($data['role'] !== 'admin' || ! $data['actif'])) {
            return back()->withInput()->with('erreur', 'Vous ne pouvez pas retirer vos propres droits administrateur.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $utilisateur->update($data);

        return redirect()->route('utilisateurs.index')->with('succes', 'Utilisateur mis à jour.');
    }

    public function destroy(Request $request, User $utilisateur)
    {
        if ($utilisateur->is($request->user())) {
            return back()->with('erreur', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $utilisateur->delete();

        return redirect()->route('utilisateurs.index')->with('succes', 'Utilisateur supprimé.');
    }
}
