<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · Gestion</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
    <div class="carte carte-corps max-w-md text-center">
        <p class="text-4xl font-bold text-marque-600">{{ $code }}</p>
        <h1 class="mt-2 text-xl">{{ $titre }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $message }}</p>
        <div class="mt-5 flex justify-center gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn-secondaire">Retour</a>
            <a href="{{ url('/') }}" class="btn-primaire">Tableau de bord</a>
        </div>
    </div>
</body>
</html>
