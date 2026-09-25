@include('errors.gabarit', [
    'code' => 403,
    'titre' => "Accès refusé",
    'message' => ($exception ?? null)?->getMessage() ?: "Vous n'avez pas les droits nécessaires pour cette action.",
])
