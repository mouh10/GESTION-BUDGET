<x-layout titre="Virement interne">
    <x-entete titre="Virement interne" sous-titre="Transfert entre deux comptes de trésorerie, via le compte 585 Virements de fonds." />

    <form method="POST" action="{{ route('tresorerie.virement.store') }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['source_id' => 'Depuis', 'destination_id' => 'Vers'] as $champ => $label)
                <div>
                    <label class="etiquette" for="{{ $champ }}">{{ $label }}</label>
                    <select id="{{ $champ }}" name="{{ $champ }}" class="champ" required>
                        @foreach ($tresoreries as $i => $t)
                            <option value="{{ $t->compte->id }}" @selected(old($champ) ? old($champ) == $t->compte->id : ($champ === 'source_id' ? $i === 0 : $i === 1))>{{ $t->compte->nom }} · {{ fcfa($t->solde) }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="date" label="Date" type="date" :valeur="now()->toDateString()" requis />
            <x-champ nom="montant" label="Montant" type="number" step="0.01" min="0.01" requis />
        </div>
        <x-champ nom="libelle" label="Libellé (facultatif)" placeholder="Ex. : Approvisionnement de la caisse" />
        <x-champ nom="reference" label="Référence" />
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer le virement</button>
            <a href="{{ route('tresorerie.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
