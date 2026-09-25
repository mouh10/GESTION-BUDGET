<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    /**
     * Autorise la requête uniquement si l'utilisateur possède l'un des rôles donnés.
     * L'administrateur passe partout. Utilisation : ->middleware('role:ordonnateur,comptable')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->aRole(...$roles)) {
            abort(403, "Vous n'avez pas les droits nécessaires pour cette action.");
        }

        return $next($request);
    }
}
