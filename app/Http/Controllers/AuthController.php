<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Audit;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function formulaire()
    {
        return view('auth.login');
    }

    public function connexion(Request $request)
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($identifiants + ['actif' => true], $request->boolean('remember'))) {
            Audit::enregistrer('echec_connexion', null, 'Échec de connexion pour '.$identifiants['email']);

            throw ValidationException::withMessages([
                'email' => 'Adresse e-mail ou mot de passe incorrect, ou compte désactivé.',
            ]);
        }

        $request->session()->regenerate();
        $utilisateur = $request->user();
        Audit::enregistrer('connexion', $utilisateur, 'Connexion'.($utilisateur->derniere_connexion ? ' (précédente : '.$utilisateur->derniere_connexion->format('d/m/Y H:i').')' : ''));
        $utilisateur->forceFill(['derniere_connexion' => now()])->saveQuietly();

        if ($utilisateur->doit_changer_mdp) {
            return redirect()->route('mot-de-passe.edit')->with('succes', 'Pour votre sécurité, choisissez un nouveau mot de passe.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function motDePasse()
    {
        return view('auth.mot-de-passe');
    }

    public function changerMotDePasse(Request $request)
    {
        $request->validate([
            'actuel' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:actuel'],
        ], [], ['actuel' => 'mot de passe actuel', 'password' => 'nouveau mot de passe']);

        $u = $request->user();
        $u->update(['password' => $request->password, 'doit_changer_mdp' => false]);
        Audit::enregistrer('mot_de_passe', $u, 'Mot de passe changé par l’utilisateur');

        return redirect()->route('dashboard')->with('succes', 'Mot de passe modifié.');
    }

    public function deconnexion(Request $request)
    {
        Audit::enregistrer('deconnexion', $request->user(), 'Déconnexion');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
