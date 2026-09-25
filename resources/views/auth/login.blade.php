<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · {{ config('app.name', 'Gestion') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50">
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Panneau de présentation --}}
    <section class="fond-grille relative hidden flex-col justify-between px-16 py-16 text-white lg:flex">
        <div class="flex items-center gap-3">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-marque-500 shadow-lg shadow-marque-500/20">
                <x-icone nom="calculatrice" class="h-7 w-7" />
            </span>
            <span class="titre text-2xl font-bold uppercase tracking-tight">{{ config('app.name', 'Gestion') }}</span>
        </div>

        <div class="max-w-xl">
            <p class="text-lg font-medium uppercase tracking-wide text-marque-400">Gestion budgétaire de l’État</p>
            <h1 class="mt-5 text-5xl font-semibold leading-[1.15] text-white xl:text-6xl">Chaque franc public, suivi de l’engagement au paiement.</h1>
            <p class="mt-8 max-w-md text-lg leading-relaxed text-slate-400">
                Crédits AE/CP, engagements, visas, mandats, recettes et comptabilité réunis dans un seul outil, pensé pour {{ config('gestion.entreprise.nom') }}.
            </p>
        </div>

        <p class="text-sm text-slate-500">© {{ now()->year }} {{ config('app.name', 'Gestion') }}</p>
    </section>

    {{-- Formulaire --}}
    <section class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-10 flex items-center gap-3 lg:hidden">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-marque-500 text-white"><x-icone nom="calculatrice" class="h-6 w-6" /></span>
                <span class="titre text-xl font-bold uppercase text-slate-900">{{ config('app.name', 'Gestion') }}</span>
            </div>

            <h1 class="text-3xl">Content de vous revoir</h1>
            <p class="mt-2 text-slate-500">Connectez-vous pour accéder à votre espace {{ config('app.name', 'Gestion') }}.</p>

            <form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="etiquette text-[15px]">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="champ py-3.5">
                    @error('email')<p class="erreur-champ">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="etiquette text-[15px]">Mot de passe</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="champ py-3.5">
                    @error('password')<p class="erreur-champ">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-3 text-[15px] text-slate-600">
                    <input type="checkbox" name="remember" class="h-5 w-5 rounded-md border-slate-300 accent-nuit"> Se souvenir de moi
                </label>
                <button class="btn-primaire w-full py-3.5 text-base">Se connecter</button>
            </form>
        </div>
    </section>
</div>
</body>
</html>
