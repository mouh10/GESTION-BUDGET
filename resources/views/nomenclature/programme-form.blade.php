<x-layout :titre="$programme->exists ? 'Modifier le programme' : 'Nouveau programme'">
    <x-entete :titre="$programme->exists ? 'Programme '.$programme->code : 'Nouveau programme'" />
    <form method="POST" action="{{ $programme->exists ? route('programmes.update', $programme) : route('programmes.store') }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        @if ($programme->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            <x-champ nom="code" label="Code" :valeur="$programme->code" requis placeholder="1001" />
            <x-champ nom="libelle" label="Libellé" :valeur="$programme->libelle" requis class="sm:col-span-2" />
        </div>
        <x-champ nom="responsable" label="Responsable de programme" :valeur="$programme->responsable" />
        <x-champ nom="objectif" label="Objectif stratégique" type="textarea" :valeur="$programme->objectif" />
        @if ($programme->exists)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $programme->actif))> Programme actif</label>
        @endif
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('programmes.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
    @if ($programme->exists)
        <form method="POST" action="{{ route('programmes.destroy', $programme) }}" class="mt-4" data-confirm="Supprimer ce programme et ses actions ?">
            @csrf @method('DELETE')<button class="btn-danger btn-petit">Supprimer le programme</button>
        </form>
    @endif
</x-layout>
