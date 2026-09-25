<x-layout :titre="$compte->exists ? 'Modifier le compte' : 'Nouveau compte'">
    <x-entete :titre="$compte->exists ? 'Compte '.$compte->numero : 'Nouveau compte'" />

    <form method="POST" action="{{ $compte->exists ? route('comptes.update', $compte) : route('comptes.store') }}" class="carte carte-corps max-w-xl space-y-4">
        @csrf
        @if ($compte->exists) @method('PUT') @endif
        <x-champ nom="numero" label="Numéro" :valeur="$compte->numero" requis inputmode="numeric"
                 aide="Le premier chiffre indique la classe. Ex. : 6011 pour un sous-compte des achats de marchandises." />
        <x-champ nom="libelle" label="Libellé" :valeur="$compte->libelle" requis />
        @if ($compte->exists)
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="actif" value="0">
                <input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $compte->actif))> Compte actif (utilisable en saisie)
            </label>
        @endif
        <div class="flex flex-wrap gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('comptes.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>

    @if ($compte->exists)
        <form method="POST" action="{{ route('comptes.destroy', $compte) }}" class="mt-4" data-confirm="Supprimer définitivement ce compte ?">
            @csrf @method('DELETE')
            <button class="btn-danger btn-petit">Supprimer ce compte</button>
        </form>
    @endif
</x-layout>
