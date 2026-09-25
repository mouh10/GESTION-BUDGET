<x-layout :titre="$marche->exists ? 'Modifier le marché' : 'Nouveau marché'">
    <x-entete :titre="$marche->exists ? 'Marché '.$marche->numero : 'Nouveau marché'" />
    <form method="POST" action="{{ $marche->exists ? route('marches.update', $marche) : route('marches.store') }}" class="carte carte-corps max-w-4xl space-y-4">
        @csrf
        @if ($marche->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            <x-champ nom="numero" label="N° du marché" :valeur="$marche->numero" requis />
            <x-champ nom="objet" label="Objet" :valeur="$marche->objet" requis class="sm:col-span-2" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="etiquette" for="tiers_id">Titulaire <span class="text-red-600">*</span></label>
                <select id="tiers_id" name="tiers_id" class="champ" required><option value="">— Choisir —</option>
                    @foreach ($fournisseurs as $t)<option value="{{ $t->id }}" @selected(old('tiers_id', $marche->tiers_id) == $t->id)>{{ $t->nom }}</option>@endforeach
                </select>
            </div>
            <x-champ nom="montant" label="Montant TTC (FCFA)" type="number" step="1" min="1" :valeur="$marche->montant !== null ? (float) $marche->montant : null" requis />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="etiquette" for="type">Type</label><select id="type" name="type" class="champ">@foreach (\App\Models\Marche::TYPES as $v => $l)<option value="{{ $v }}" @selected(old('type', $marche->type) === $v)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="etiquette" for="mode_passation">Mode de passation</label><select id="mode_passation" name="mode_passation" class="champ">@foreach (\App\Models\Marche::MODES as $v => $l)<option value="{{ $v }}" @selected(old('mode_passation', $marche->mode_passation) === $v)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="etiquette" for="statut">Statut</label><select id="statut" name="statut" class="champ">@foreach (\App\Models\Marche::STATUTS as $v => $l)<option value="{{ $v }}" @selected(old('statut', $marche->statut) === $v)>{{ $l }}</option>@endforeach</select></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-champ nom="date_signature" label="Date de signature" type="date" :valeur="$marche->date_signature?->toDateString()" />
            <x-champ nom="date_fin" label="Date de fin d’exécution" type="date" :valeur="$marche->date_fin?->toDateString()" />
            <div>
                <label class="etiquette" for="ligne_credit_id">Imputation prévue</label>
                <select id="ligne_credit_id" name="ligne_credit_id" class="champ"><option value="">—</option>
                    @foreach ($lignes as $l)<option value="{{ $l->id }}" @selected(old('ligne_credit_id', $marche->ligne_credit_id) == $l->id)>{{ $l->imputation() }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="flex gap-2"><button class="btn-primaire">Enregistrer</button><a href="{{ $marche->exists ? route('marches.show', $marche) : route('marches.index') }}" class="btn-secondaire">Annuler</a></div>
    </form>
</x-layout>
