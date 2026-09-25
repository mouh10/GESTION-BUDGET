<?php

namespace App\Http\Controllers;

use App\Models\Exercice;

abstract class Controller
{
    /**
     * Exercice comptable sur lequel l'utilisateur travaille.
     */
    protected function exercice(): Exercice
    {
        $exercice = Exercice::courant();

        abort_if(! $exercice, 409, "Aucun exercice comptable n'existe. Créez-en un dans Paramètres › Exercices.");

        return $exercice;
    }
}
