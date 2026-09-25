<?php

if (! function_exists('montant')) {
    /**
     * Formate un montant à la française : 1 250 000.
     */
    function montant($valeur, int $decimales = 0): string
    {
        $valeur = (float) ($valeur ?? 0);
        if (abs($valeur) < 0.005) {
            $valeur = 0.0;
        }

        return number_format($valeur, $decimales, ',', "\u{202F}");
    }
}

if (! function_exists('fcfa')) {
    /**
     * Formate un montant suivi de la devise : 1 250 000 FCFA.
     */
    function fcfa($valeur): string
    {
        return montant($valeur).' '.config('gestion.devise', 'FCFA');
    }
}

if (! function_exists('date_fr')) {
    function date_fr($date, string $format = 'd/m/Y'): string
    {
        if (! $date) {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($date)->format($format);
    }
}
