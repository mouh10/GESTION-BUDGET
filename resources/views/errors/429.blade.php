@include('errors.gabarit', [
    'code' => 429,
    'titre' => "Trop de tentatives",
    'message' => ($exception ?? null)?->getMessage() ?: "Patientez une minute avant de réessayer.",
])
