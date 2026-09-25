<x-layout :titre="'Recouvrement '.$titre->numero">
    <x-entete :titre="'Recouvrement du titre '.$titre->numero" :sous-titre="$titre->tiers->nom.' · reste à recouvrer '.fcfa($titre->reste())" />
    <form method="POST" action="{{ route('titres.recouvrer', $titre) }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        <div>
            <label class="etiquette" for="compte_tresorerie_id">Encaissé sur</label>
            <select id="compte_tresorerie_id" name="compte_tresorerie_id" class="champ" required>@foreach ($tresoreries as $t)<option value="{{ $t->id }}">{{ $t->nom }}</option>@endforeach</select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="date" label="Date" type="date" :valeur="now()->toDateString()" requis />
            <x-champ nom="montant" label="Montant" type="number" step="1" min="1" :max="$titre->reste()" :valeur="$titre->reste()" requis />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="etiquette" for="mode">Mode</label><select id="mode" name="mode" class="champ">@foreach (\App\Models\MouvementTresorerie::MODES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
            <x-champ nom="reference" label="Référence (quittance…)" />
        </div>
        <div class="flex gap-2"><button class="btn-primaire">Enregistrer</button><a href="{{ route('titres.show', $titre) }}" class="btn-secondaire">Annuler</a></div>
    </form>
</x-layout>
