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
        return view('utilisateurs.index', ['utilisateurs' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('utilisateurs.form', ['utilisateur' => new User(['role' => 'comptable', 'actif' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['actif'] = $request->boolean('actif');

        User::create($data);

        return redirect()->route('utilisateurs.index')->with('succes', 'Utilisateur créé.');
    }

    public function edit(User $utilisateur)
    {
        return view('utilisateurs.form', compact('utilisateur'));
    }

    public function update(Request $request, User $utilisateur)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($utilisateur->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        $data['actif'] = $request->boolean('actif');

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
