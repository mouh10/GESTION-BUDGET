<?php

use App\Services\Sauvegarde;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('gestion:sauvegarder', function (Sauvegarde $sauvegarde) {
    $r = $sauvegarde->lancer();
    $this->info('Sauvegarde créée : '.$r['fichier'].' ('.$sauvegarde->tailleLisible($r['taille']).')');
    if ($r['supprimees']) {
        $this->line($r['supprimees'].' ancienne(s) sauvegarde(s) supprimée(s).');
    }
})->purpose('Sauvegarde la base de données et les pièces jointes dans une archive ZIP');

// Sauvegarde automatique chaque nuit (nécessite « php artisan schedule:run » toutes les minutes).
Schedule::command('gestion:sauvegarder')->dailyAt(config('gestion.sauvegardes.heure', '02:00'))->withoutOverlapping();
