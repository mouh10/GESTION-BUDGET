<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\VerifierRole::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\ExigerNouveauMotDePasse::class);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Les erreurs métier (écriture déséquilibrée, exercice clôturé...) reviennent
        // au formulaire avec un message lisible au lieu d'une page d'erreur.
        $exceptions->render(function (\App\Exceptions\GestionException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('erreur', $e->getMessage());
        });
    })->create();
