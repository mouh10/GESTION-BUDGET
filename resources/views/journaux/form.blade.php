<x-layout :titre="$journal->exists ? 'Modifier le journal' : 'Nouveau journal'">
    <x-entete :titre="$journal->exists ? 'Journal '.$journal->code : 'Nouveau journal'" />

    <form method="POST" action="{{ $journal->exists ? route('journaux.update', $journal) : route('journaux.store') }}" class="carte carte-corps max-w-xl space-y-4">
        @csrf
        @if ($journal->exists) @method('PUT') @endif
        @unless ($journal->exists)
            <x-champ nom="code" label="Code (5 caractères max.)" :valeur="$journal->code" requis maxlength="5" />
        @endunless
        <x-champ nom="libelle" label="Libellé" :valeur="$journal->libelle" requis />
        <div>
            <label class="etiquette" for="type">Type</label>
            <select id="type" name="type" class="champ">
                @foreach (\App\Models\Journal::TYPES as $v => $l)
                    <option value="{{ $v }}" @selected(old('type', $journal->type) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('journaux.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
