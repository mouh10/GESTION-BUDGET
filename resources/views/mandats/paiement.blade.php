@php $e = $mandat->liquidation->engagement; @endphp
<x-layout :titre="'Paiement '.$mandat->numero">
    <x-entete :titre="'Paiement du mandat '.$mandat->numero" :sous-titre="$e->tiers->nom.' · '.fcfa($mandat->montant)" />
    <form method="POST" action="{{ route('mandats.payer', $mandat) }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        <div>
            <label class="etiquette" for="compte_tresorerie_id">Payé depuis</label>
            <select id="compte_tresorerie_id" name="compte_tresorerie_id" class="champ" required>
                @foreach ($tresoreries as $t)<option value="{{ $t->id }}" @selected(old('compte_tresorerie_id') == $t->id)>{{ $t->nom }} · solde {{ fcfa($t->solde()) }}</option>@endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-champ nom="date" label="Date de paiement" type="date" :valeur="now()->toDateString()" requis />
            <div>
                <label class="etiquette" for="mode">Mode</label>
                <select id="mode" name="mode" class="champ">@foreach (\App\Models\MouvementTresorerie::MODES as $v => $l)<option value="{{ $v }}" @selected(old('mode', 'virement') === $v)>{{ $l }}</option>@endforeach</select>
            </div>
            <x-champ nom="reference" label="Référence (ordre de virement, chèque)" />
        </div>
        @if ($e->tiers->rib)<p class="text-sm text-slate-600">RIB du bénéficiaire : {{ $e->tiers->rib }}</p>@endif
        <div class="flex gap-2"><button class="btn-primaire">Enregistrer le paiement</button><a href="{{ route('mandats.show', $mandat) }}" class="btn-secondaire">Annuler</a></div>
    </form>
</x-layout>
