@php $enc = $type === 'encaissement'; @endphp
<x-layout :titre="($enc ? 'Encaissement' : 'Décaissement').' · '.$tresorerie->nom">
    <x-entete :titre="($enc ? 'Encaissement sur ' : 'Décaissement depuis ').$tresorerie->nom"
              :sous-titre="'Solde actuel : '.fcfa($tresorerie->solde()).'. Les paiements de mandats et les recouvrements de titres se font depuis le mandat ou le titre.'" />

    <form method="POST" action="{{ route('tresorerie.mouvements.store', $tresorerie) }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="etiquette" for="type">Type</label>
                <select id="type" name="type" class="champ">
                    @foreach (\App\Models\MouvementTresorerie::TYPES as $v => $l)
                        <option value="{{ $v }}" @selected(old('type', $type) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <x-champ nom="date" label="Date" type="date" :valeur="now()->toDateString()" requis />
            <x-champ nom="montant" label="Montant" type="number" step="0.01" min="0.01" requis />
        </div>
        <x-champ nom="libelle" label="Libellé" requis :placeholder="$enc ? 'Ex. : Apport en compte courant' : 'Ex. : Loyer du mois de mars'" />
        <div>
            <label class="etiquette" for="compte_id">Compte de contrepartie <span class="text-red-600">*</span></label>
            <select id="compte_id" name="compte_id" class="champ" required>
                <option value="">— Choisir —</option>
                @foreach ($comptes as $c)
                    <option value="{{ $c->id }}" @selected(old('compte_id') == $c->id)>{{ $c->numero }} - {{ $c->libelle }}</option>
                @endforeach
            </select>
            <p class="aide">{{ $enc ? 'Ex. : 706 Services vendus, 462 Associés, 758 Produits divers.' : 'Ex. : 622 Locations, 661 Salaires, 631 Frais bancaires.' }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="etiquette" for="tiers_id">Tiers (facultatif)</label>
                <select id="tiers_id" name="tiers_id" class="champ">
                    <option value="">—</option>
                    @foreach ($tiers as $t)
                        <option value="{{ $t->id }}" @selected(old('tiers_id') == $t->id)>{{ $t->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="etiquette" for="mode">Mode</label>
                <select id="mode" name="mode" class="champ">
                    @foreach (\App\Models\MouvementTresorerie::MODES as $v => $l)
                        <option value="{{ $v }}" @selected(old('mode', $tresorerie->type === 'caisse' ? 'especes' : ($tresorerie->type === 'mobile_money' ? 'mobile_money' : 'virement')) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <x-champ nom="reference" label="Référence" />
        </div>
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ route('tresorerie.show', $tresorerie) }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
