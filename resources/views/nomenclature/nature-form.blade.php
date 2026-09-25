@php $type = old('type', $nature->type); @endphp
<x-layout :titre="$nature->exists ? 'Modifier la nature' : 'Nouvelle nature'">
    <x-entete :titre="$nature->exists ? 'Nature '.$nature->code : 'Nouvelle nature'" />
    <form method="POST" action="{{ $nature->exists ? route('natures.update', $nature) : route('natures.store') }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        @if ($nature->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="etiquette" for="type">Type</label>
                <select id="type" name="type" class="champ">
                    @foreach (\App\Models\Nature::TYPES as $v => $l)<option value="{{ $v }}" @selected($type === $v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <x-champ nom="code" label="Code" :valeur="$nature->code" requis />
            <div>
                <label class="etiquette" for="titre">Titre / catégorie</label>
                <select id="titre" name="titre" class="champ">
                    <optgroup label="Dépenses (titres)">
                        @foreach (\App\Models\Nature::TITRES_DEPENSE as $v => $l)<option value="{{ $v }}" @selected(old('titre', $nature->titre) == $v && $type === 'depense')>T{{ $v }} — {{ $l }}</option>@endforeach
                    </optgroup>
                    <optgroup label="Recettes (catégories)">
                        @foreach (\App\Models\Nature::CATEGORIES_RECETTE as $v => $l)<option value="{{ $v }}" @selected(old('titre', $nature->titre) == $v && $type === 'recette')>C{{ $v }} — {{ $l }}</option>@endforeach
                    </optgroup>
                </select>
            </div>
        </div>
        <x-champ nom="libelle" label="Libellé" :valeur="$nature->libelle" requis />
        <div>
            <label class="etiquette" for="compte_id">Imputation comptable</label>
            <select id="compte_id" name="compte_id" class="champ">
                <option value="">— Aucune —</option>
                @foreach ($comptes as $c)<option value="{{ $c->id }}" @selected(old('compte_id', $nature->compte_id) == $c->id)>{{ $c->numero }} - {{ $c->libelle }}</option>@endforeach
            </select>
            <p class="aide">Compte débité à la prise en charge d’un mandat (dépense) ou crédité à l’émission d’un titre (recette).</p>
        </div>
        @if ($nature->exists)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $nature->actif))> Nature active</label>
        @endif
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('natures.index', ['type' => $type]) }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
