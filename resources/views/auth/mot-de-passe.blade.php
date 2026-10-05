<x-layout titre="Mon mot de passe">
    <x-entete titre="Changer mon mot de passe" :sous-titre="auth()->user()->doit_changer_mdp ? 'Votre mot de passe est provisoire : choisissez-en un personnel pour accéder à l’application.' : 'Au moins 8 caractères, avec des lettres et des chiffres.'" />

    <form method="POST" action="{{ route('mot-de-passe.update') }}" class="carte carte-corps max-w-xl space-y-4">
        @csrf @method('PUT')
        <x-champ nom="actuel" label="Mot de passe actuel" type="password" requis autocomplete="current-password" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="password" label="Nouveau mot de passe" type="password" requis autocomplete="new-password" aide="8 caractères minimum, lettres et chiffres." />
            <x-champ nom="password_confirmation" label="Confirmation" type="password" requis autocomplete="new-password" />
        </div>
        <button class="btn-primaire">Enregistrer le nouveau mot de passe</button>
    </form>
</x-layout>
