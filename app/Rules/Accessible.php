<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Comme « exists », mais en passant par le modèle : les restrictions par service
 * s'appliquent, un utilisateur ne peut donc pas viser l'enregistrement d'un autre service.
 */
class Accessible implements ValidationRule
{
    public function __construct(protected string $modele, protected ?Closure $contrainte = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $q = $this->modele::query()->whereKey($value);
        if ($this->contrainte) {
            ($this->contrainte)($q);
        }
        if (! $q->exists()) {
            $fail('La valeur choisie pour :attribute est invalide ou hors de votre périmètre.');
        }
    }
}
