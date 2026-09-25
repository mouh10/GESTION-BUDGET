@props(['statut'])
@php
    $styles = [
        'brouillon' => ['badge-gris', 'Brouillon'],
        'soumis' => ['badge-orange', 'Soumis au visa'],
        'vise' => ['badge-vert', 'Visé'],
        'rejete' => ['badge-rouge', 'Rejeté'],
        'annule' => ['badge-gris', 'Annulé'],
        'annulee' => ['badge-gris', 'Annulée'],
        'validee' => ['badge-bleu', 'Validée'],
        'emis' => ['badge-orange', 'Émis'],
        'pris_en_charge' => ['badge-bleu', 'Pris en charge'],
        'paye' => ['badge-vert', 'Payé'],
        'partiellement_recouvre' => ['badge-bleu', 'Partiellement recouvré'],
        'recouvre' => ['badge-vert', 'Recouvré'],
        'approuve' => ['badge-vert', 'Approuvé'],
        'en_cours' => ['badge-bleu', 'En cours'],
        'termine' => ['badge-vert', 'Terminé'],
        'resilie' => ['badge-rouge', 'Résilié'],
        'retard' => ['badge-rouge', 'En retard'],
    ];
    [$classe, $libelle] = $styles[$statut] ?? ['badge-gris', ucfirst(str_replace('_', ' ', $statut))];
@endphp
<span class="{{ $classe }}">{{ $libelle }}</span>
