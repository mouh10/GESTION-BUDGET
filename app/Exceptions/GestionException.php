<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreur métier affichée telle quelle à l'utilisateur
 * (écriture déséquilibrée, exercice clôturé, montant trop élevé...).
 */
class GestionException extends RuntimeException
{
}
