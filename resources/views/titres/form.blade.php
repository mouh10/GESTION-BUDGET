<x-layout titre="Émettre un titre de recette">
    <x-entete titre="Émettre un titre de recette" sous-titre="Le titre est pris en charge en comptabilité (débit du redevable, crédit du compte de produit de la nature)." />
    <form method="POST" action="{{ route('titres.store') }}" class="carte carte-corps max-w-3xl space-y-4">
        @csrf
        <div>
            <label class="etiquette" for="prevision_recette_id">Prévision de recette <span class="text-red-600">*</span></label>
            <select id="prevision_recette_id" name="prevision_recette_id" class="champ" required><option value="">— Choisir —</option>
                @foreach ($previsions as $p)<option value="{{ $p->id }}" @selected(old('prevision_recette_id', $titre->prevision_recette_id) == $p->id)>{{ $p->intitule() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="tiers_id">Redevable <span class="text-red-600">*</span></label>
            <select id="tiers_id" name="tiers_id" class="champ" required><option value="">— Choisir —</option>
                @foreach ($redevables as $r)<option value="{{ $r->id }}" @selected(old('tiers_id') == $r->id)>{{ $r->nom }}</option>@endforeach
            </select>
            <a href="{{ route('tiers.create', ['type' => 'redevable']) }}" class="lien mt-1 inline-block text-xs">+ Nouveau redevable</a>
        </div>
        <x-champ nom="objet" label="Objet" requis />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-champ nom="date" label="Date d’émission" type="date" :valeur="$titre->date?->toDateString()" requis />
            <x-champ nom="date_echeance" label="Échéance" type="date" />
            <x-champ nom="montant" label="Montant (FCFA)" type="number" step="1" min="1" requis />
        </div>
        <div class="flex gap-2"><button class="btn-primaire">Émettre le titre</button><a href="{{ route('titres.index') }}" class="btn-secondaire">Annuler</a></div>
    </form>
</x-layout>
