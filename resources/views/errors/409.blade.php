@include('errors.gabarit', [
    'code' => 409,
    'titre' => "Action impossible",
    'message' => ($exception ?? null)?->getMessage() ?: "L'opération ne peut pas être réalisée dans l'état actuel.",
])
