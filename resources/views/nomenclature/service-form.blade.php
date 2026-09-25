<x-layout :titre="$service->exists ? 'Modifier le service' : 'Nouveau service'">
    <x-entete :titre="$service->exists ? 'Service '.$service->code : 'Nouveau service'" />
    <form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        @if ($service->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            <x-champ nom="code" label="Code (sigle)" :valeur="$service->code" requis placeholder="DAGE" />
            <x-champ nom="libelle" label="Libellé" :valeur="$service->libelle" requis class="sm:col-span-2" />
        </div>
        <x-champ nom="responsable" label="Responsable" :valeur="$service->responsable" />
        @if ($service->exists)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $service->actif))> Service actif</label>
        @endif
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('services.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
    @if ($service->exists)
        <form method="POST" action="{{ route('services.destroy', $service) }}" class="mt-4" data-confirm="Supprimer ce service ?">
            @csrf @method('DELETE')<button class="btn-danger btn-petit">Supprimer</button>
        </form>
    @endif
</x-layout>
