<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Numerotation
{
    /**
     * Prochain numéro séquentiel pour un préfixe donné.
     * Exemple : suivant('FV-2026-', 'factures', 'numero') => FV-2026-0007
     */
    public static function suivant(string $prefixe, string $table, string $colonne, int $longueur = 4): string
    {
        $numeros = DB::table($table)
            ->where($colonne, 'like', $prefixe.'%')
            ->pluck($colonne);

        $max = 0;
        foreach ($numeros as $numero) {
            $suffixe = substr($numero, strlen($prefixe));
            if (ctype_digit($suffixe)) {
                $max = max($max, (int) $suffixe);
            }
        }

        return $prefixe.str_pad((string) ($max + 1), $longueur, '0', STR_PAD_LEFT);
    }
}
