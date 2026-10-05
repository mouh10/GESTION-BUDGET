<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Comptabilite::class);
        $this->app->singleton(\App\Services\Etats::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');

        // Noms courts et stables pour les objets cités dans le journal d'audit et les pièces jointes.
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'engagement' => \App\Models\Engagement::class,
            'liquidation' => \App\Models\Liquidation::class,
            'mandat' => \App\Models\Mandat::class,
            'titre' => \App\Models\TitreRecette::class,
            'modification' => \App\Models\Modification::class,
            'ligne_credit' => \App\Models\LigneCredit::class,
            'prevision' => \App\Models\PrevisionRecette::class,
            'marche' => \App\Models\Marche::class,
            'tiers' => \App\Models\Tiers::class,
            'tresorerie' => \App\Models\CompteTresorerie::class,
            'mouvement' => \App\Models\MouvementTresorerie::class,
            'ecriture' => \App\Models\Ecriture::class,
            'utilisateur' => \App\Models\User::class,
            'exercice' => \App\Models\Exercice::class,
            'programme' => \App\Models\Programme::class,
            'action' => \App\Models\Action::class,
            'service' => \App\Models\Service::class,
            'nature' => \App\Models\Nature::class,
            'compte' => \App\Models\Compte::class,
            'piece' => \App\Models\PieceJointe::class,
        ]);
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');
    }
}
