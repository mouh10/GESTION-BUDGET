<x-layout :titre="$prevision->exists ? 'Modifier la prévision' : 'Nouvelle prévision de recette'">
    <x-entete :titre="$prevision->exists ? 'Modifier la prévision' : 'Nouvelle prévision de recette'" />
    <form method="POST" action="{{ $prevision->exists ? route('previsions.update', $prevision) : route('previsions.store') }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        @if ($prevision->exists) @method('PUT') @endif
        <div>
            <label class="etiquette" for="nature_id">Nature de recette <span class="text-red-600">*</span></label>
            <select id="nature_id" name="nature_id" class="champ" required>
                @foreach ($natures as $n)<option value="{{ $n->id }}" @selected(old('nature_id', $prevision->nature_id) == $n->id)>{{ $n->code }} — {{ $n->libelle }} ({{ $n->libelleTitre() }})</option>@endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="service_id">Service chargé du recouvrement</label>
            <select id="service_id" name="service_id" class="champ">
                <option value="">—</option>
                @foreach ($services as $s)<option value="{{ $s->id }}" @selected(old('service_id', $prevision->service_id) == $s->id)>{{ $s->code }} — {{ $s->libelle }}</option>@endforeach
            </select>
        </div>
        <x-champ nom="libelle" label="Libellé particulier" :valeur="$prevision->libelle" />
        <x-champ nom="montant_prevu" label="Montant prévu (FCFA)" type="number" step="1" min="0" :valeur="$prevision->montant_prevu !== null ? (float) $prevision->montant_prevu : null" requis />
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('previsions.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
    @if ($prevision->exists)
        <form method="POST" action="{{ route('previsions.destroy', $prevision) }}" class="mt-4" data-confirm="Supprimer cette prévision ?">
            @csrf @method('DELETE')<button class="btn-danger btn-petit">Supprimer</button>
        </form>
    @endif
</x-layout>
