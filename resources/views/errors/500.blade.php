@include('errors.gabarit', [
    'code' => 500,
    'titre' => "Erreur interne",
    'message' => "Une erreur inattendue s'est produite. Consultez storage/logs/laravel.log.",
])
