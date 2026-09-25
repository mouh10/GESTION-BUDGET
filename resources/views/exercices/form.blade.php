<x-layout :titre="$exercice->exists ? 'Modifier l’exercice' : 'Nouvel exercice'">
    <x-entete :titre="$exercice->exists ? 'Modifier '.$exercice->libelle : 'Nouvel exercice'" />

    <form method="POST" action="{{ $exercice->exists ? route('exercices.update', $exercice) : route('exercices.store') }}" class="carte carte-corps max-w-xl space-y-4">
        @csrf
        @if ($exercice->exists) @method('PUT') @endif
        <x-champ nom="libelle" label="Libellé" :valeur="$exercice->libelle" requis />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="date_debut" label="Date de début" type="date" :valeur="$exercice->date_debut?->toDateString()" requis />
            <x-champ nom="date_fin" label="Date de fin" type="date" :valeur="$exercice->date_fin?->toDateString()" requis />
        </div>
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('exercices.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
