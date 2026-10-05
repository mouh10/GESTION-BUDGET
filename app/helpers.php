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

        return number_format($valeur, $decimales, ',', "\u{00A0}");
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

if (! function_exists('module_actif')) {
    /**
     * Module de l'écran courant, pour les bandes de couleur et le fil d'Ariane.
     *
     * @return array{0: string, 1: string} [clé CSS, libellé]
     */
    function module_actif(): array
    {
        $route = request()->route()?->getName() ?? '';
        $tiers = request()->route('tiers');
        $redevable = request('type') === 'redevable' || ($tiers instanceof \App\Models\Tiers && $tiers->estRedevable());

        return match (true) {
            str_starts_with($route, 'credits.'), str_starts_with($route, 'previsions.'), str_starts_with($route, 'modifications.') => ['budget', 'Budget'],
            str_starts_with($route, 'tiers.') && $redevable, str_starts_with($route, 'titres.') => ['recettes', 'Recettes'],
            str_starts_with($route, 'engagements.'), str_starts_with($route, 'mandats.'), str_starts_with($route, 'marches.'), str_starts_with($route, 'tiers.') => ['depenses', 'Dépenses'],
            str_starts_with($route, 'tresorerie.') => ['tresorerie', 'Trésorerie'],
            str_starts_with($route, 'execution.') => ['execution', 'Exécution'],
            str_starts_with($route, 'ecritures.'), str_starts_with($route, 'etats.') => ['comptabilite', 'Comptabilité'],
            str_starts_with($route, 'programmes.'), str_starts_with($route, 'services.'), str_starts_with($route, 'natures.'),
            str_starts_with($route, 'comptes.'), str_starts_with($route, 'journaux.'), str_starts_with($route, 'exercices.'),
            str_starts_with($route, 'utilisateurs.'), str_starts_with($route, 'audit.'), str_starts_with($route, 'sauvegardes.') => ['parametres', 'Paramètres'],
            default => ['accueil', 'Accueil'],
        };
    }
}

if (! function_exists('en_impression')) {
    /** Vrai quand la page est ouverte pour impression (?impression=1). */
    function en_impression(): bool
    {
        return request()->boolean('impression');
    }
}

if (! function_exists('par_page')) {
    /**
     * Nombre de lignes par page ; à l'impression, toute la liste tient sur une seule page
     * (dans la limite de 5 000 lignes).
     */
    function par_page(int $defaut = 25): int
    {
        return en_impression() ? 5000 : $defaut;
    }
}

if (! function_exists('nombre_en_lettres')) {
    /** Écrit un entier positif en toutes lettres (orthographe traditionnelle : « quatre-vingts », « deux cents »). */
    function nombre_en_lettres(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }
        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize'];
        $dizaines = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

        $moinsDeCent = function (int $n, bool $final) use (&$moinsDeCent, $unites, $dizaines): string {
            if ($n < 17) {
                return $unites[$n];
            }
            if ($n < 20) {
                return 'dix-'.$unites[$n - 10];
            }
            $d = intdiv($n, 10);
            $u = $n % 10;
            if ($d === 7 || $d === 9) {
                $base = $d === 7 ? 'soixante' : 'quatre-vingt';

                return $base.($d === 7 && $u === 1 ? ' et ' : '-').$moinsDeCent(10 + $u, $final);
            }
            if ($d === 8) {
                return $u === 0 ? ($final ? 'quatre-vingts' : 'quatre-vingt') : 'quatre-vingt-'.$unites[$u];
            }
            if ($u === 0) {
                return $dizaines[$d];
            }

            return $dizaines[$d].($u === 1 ? ' et un' : '-'.$unites[$u]);
        };

        $moinsDeMille = function (int $n, bool $final) use ($moinsDeCent, $unites): string {
            $c = intdiv($n, 100);
            $r = $n % 100;
            $texte = '';
            if ($c > 0) {
                $texte = $c === 1 ? 'cent' : $unites[$c].' cent'.($r === 0 && $final ? 's' : '');
            }
            if ($r > 0) {
                $texte .= ($texte ? ' ' : '').$moinsDeCent($r, $final);
            }

            return $texte;
        };

        $parties = [];
        foreach ([[1000000000, 'milliard'], [1000000, 'million']] as [$valeur, $nom]) {
            $q = intdiv($n, $valeur);
            if ($q > 0) {
                $parties[] = $moinsDeMille($q, true).' '.$nom.($q > 1 ? 's' : '');
                $n %= $valeur;
            }
        }
        $milliers = intdiv($n, 1000);
        if ($milliers > 0) {
            $parties[] = $milliers === 1 ? 'mille' : $moinsDeMille($milliers, false).' mille';
            $n %= 1000;
        }
        if ($n > 0) {
            $parties[] = $moinsDeMille($n, true);
        }

        return implode(' ', $parties);
    }
}

if (! function_exists('montant_en_lettres')) {
    /** « Huit millions (8 000 000) de francs CFA » : mention portée sur les pièces de dépense et de recette. */
    function montant_en_lettres($montant): string
    {
        $n = (int) round(abs((float) $montant));
        $lettres = nombre_en_lettres($n);
        // « de » devant « francs » après million(s) / milliard(s) employés seuls.
        $de = preg_match('/(millions?|milliards?)$/', $lettres) ? ' de' : '';

        return mb_strtoupper(mb_substr($lettres, 0, 1)).mb_substr($lettres, 1).' ('.montant($n).')'.$de.' francs CFA';
    }
}
