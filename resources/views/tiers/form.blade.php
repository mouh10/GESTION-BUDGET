@php $type = old('type', $tiers->type); @endphp
<x-layout :titre="$tiers->exists ? $tiers->nom : 'Nouveau tiers'">
    <x-entete :titre="$tiers->exists ? 'Modifier '.$tiers->nom : ($type === 'redevable' ? 'Nouveau redevable' : 'Nouveau fournisseur')" />

    <form method="POST" action="{{ $tiers->exists ? route('tiers.update', $tiers) : route('tiers.store') }}" class="carte carte-corps max-w-3xl space-y-4">
        @csrf
        @if ($tiers->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="etiquette" for="type">Type</label>
                <select id="type" name="type" class="champ">
                    @foreach (\App\Models\Tiers::TYPES as $v => $l)
                        <option value="{{ $v }}" @selected($type === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <x-champ nom="code" label="Code" :valeur="$tiers->code" aide="Laisser vide pour une numérotation automatique." />
            <x-champ nom="ninea" label="NINEA" :valeur="$tiers->ninea" />
        </div>
        <x-champ nom="rib" label="RIB / coordonnées bancaires" :valeur="$tiers->rib" />
        <x-champ nom="nom" label="Nom / raison sociale" :valeur="$tiers->nom" requis />
        <x-champ nom="adresse" label="Adresse" :valeur="$tiers->adresse" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="telephone" label="Téléphone" :valeur="$tiers->telephone" />
            <x-champ nom="email" label="E-mail" type="email" :valeur="$tiers->email" />
        </div>
        <div>
            <label class="etiquette" for="compte_id">Compte collectif</label>
            <select id="compte_id" name="compte_id" class="champ">
                @foreach ($comptes as $c)
                    <option value="{{ $c->id }}" @selected(old('compte_id', $tiers->compte_id) == $c->id)>{{ $c->numero }} - {{ $c->libelle }}</option>
                @endforeach
            </select>
            <p class="aide">Fournisseurs : 4011 · Personnel : 422 · Organismes sociaux : 431 · Redevables : 4111.</p>
        </div>
        @if ($tiers->exists)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $tiers->actif))> Actif
            </label>
        @endif
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ $tiers->exists ? route('tiers.show', $tiers) : route('tiers.index', ['type' => $type]) }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
