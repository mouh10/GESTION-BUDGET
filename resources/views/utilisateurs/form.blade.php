<x-layout :titre="$utilisateur->exists ? 'Modifier l’utilisateur' : 'Nouvel utilisateur'">
    <x-entete :titre="$utilisateur->exists ? $utilisateur->name : 'Nouvel utilisateur'" />

    <form method="POST" action="{{ $utilisateur->exists ? route('utilisateurs.update', $utilisateur) : route('utilisateurs.store') }}" class="carte carte-corps max-w-xl space-y-4">
        @csrf
        @if ($utilisateur->exists) @method('PUT') @endif
        <x-champ nom="name" label="Nom complet" :valeur="$utilisateur->name" requis />
        <x-champ nom="email" label="Adresse e-mail" type="email" :valeur="$utilisateur->email" requis />
        <div>
            <label class="etiquette" for="role">Rôle</label>
            <select id="role" name="role" class="champ">
                @foreach (\App\Models\User::ROLES as $v => $l)
                    <option value="{{ $v }}" @selected(old('role', $utilisateur->role) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="password" label="Mot de passe" type="password" :requis="! $utilisateur->exists" autocomplete="new-password"
                     :aide="$utilisateur->exists ? 'Laisser vide pour ne pas changer.' : '8 caractères minimum.'" />
            <x-champ nom="password_confirmation" label="Confirmation" type="password" :requis="! $utilisateur->exists" autocomplete="new-password" />
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $utilisateur->actif))> Compte actif
        </label>
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('utilisateurs.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
