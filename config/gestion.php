<?php

return [
    // Structure (imprimée sur les bons d'engagement, mandats et titres).
    'entreprise' => [
        'nom' => env('GESTION_ENTREPRISE_NOM', env('APP_NAME', 'Gestion')),
        'tutelle' => env('GESTION_ENTREPRISE_TUTELLE', 'République du Sénégal'),
        'devise_nationale' => env('GESTION_DEVISE_NATIONALE', 'Un Peuple – Un But – Une Foi'),
        'ville' => env('GESTION_VILLE', 'Dakar'),
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

    // Sauvegardes automatiques (base de données + pièces jointes).
    'sauvegardes' => [
        'dossier' => env('GESTION_SAUVEGARDES_DOSSIER'),          // par défaut storage/app/sauvegardes
        'conserver_jours' => (int) env('GESTION_SAUVEGARDES_JOURS', 30),
        'heure' => env('GESTION_SAUVEGARDES_HEURE', '02:00'),
        'pg_dump' => env('GESTION_PG_DUMP', 'pg_dump'),           // Windows : C:\Program Files\PostgreSQL\16\bin\pg_dump.exe
        'mysqldump' => env('GESTION_MYSQLDUMP', 'mysqldump'),
    ],
];
