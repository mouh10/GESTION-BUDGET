<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Tant que le mot de passe provisoire n'est pas changé, seul l'écran de changement est accessible. */
class ExigerNouveauMotDePasse
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->doit_changer_mdp && ! $request->routeIs('mot-de-passe.*', 'logout')) {
            return redirect()->route('mot-de-passe.edit')->with('erreur', 'Vous devez choisir un nouveau mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
