<?php

return [
    // Structure (imprimée sur les bons d'engagement, mandats et titres).
    'entreprise' => [
        'nom' => env('GESTION_ENTREPRISE_NOM', env('APP_NAME', 'Gestion')),
        'tutelle' => env('GESTION_ENTREPRISE_TUTELLE', 'République du Sénégal'),
        'adresse' => env('GESTION_ENTREPRISE_ADRESSE', ''),
        'telephone' => env('GESTION_ENTREPRISE_TELEPHONE', ''),
        'ninea' => env('GESTION_ENTREPRISE_NINEA', ''),
    ],

    // Devise affichée dans l'application.
    'devise' => env('GESTION_DEVISE', 'FCFA'),

    // Comptes utilisés par les écritures automatiques.
    'comptes' => [
        'redevables' => '4111',
        'fournisseurs' => '4011',
        'virements_internes' => '585',  // Virements de fonds
        'capital' => '121',             // Contrepartie par défaut des soldes d'ouverture de trésorerie
    ],

    // Codes des journaux utilisés automatiquement.
    'journaux' => [
        'depenses' => 'DP',    // prise en charge des mandats
        'recettes' => 'RC',    // prise en charge des titres de recette
        'operations_diverses' => 'OD',
    ],

    // Seuil d'alerte : une ligne est signalée quand son taux d'engagement dépasse ce pourcentage.
    'alerte_budget' => 90,
];
