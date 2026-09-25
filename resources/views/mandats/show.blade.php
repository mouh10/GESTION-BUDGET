@php $e = $mandat->liquidation->engagement; $l = $e->ligneCredit; $u = auth()->user(); @endphp
<x-layout :titre="'Mandat '.$mandat->numero">
    <x-entete :titre="'Mandat '.$mandat->numero" :sous-titre="$e->tiers->nom.' — '.$e->objet">
        @if ($u->estComptable() && $mandat->statut === 'emis')
            <form method="POST" action="{{ route('mandats.prendre-en-charge', $mandat) }}" data-confirm="Prendre en charge ce mandat ? L’écriture comptable sera passée.">@csrf<button class="btn-primaire">Prendre en charge</button></form>
        @endif
        @if ($u->estComptable() && $mandat->statut === 'pris_en_charge')
            <a href="{{ route('mandats.paiement', $mandat) }}" class="btn-primaire"><x-icone nom="portefeuille" class="h-4 w-4" /> Payer</a>
        @endif
        <a href="{{ route('mandats.imprimer', $mandat) }}" target="_blank" class="btn-secondaire">Imprimer</a>
        <a href="{{ route('engagements.show', $e) }}" class="btn-secondaire">Engagement {{ $e->numero }}</a>
    </x-entete>

    @if ($mandat->statut === 'rejete')
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Rejeté par le comptable :</strong> {{ $mandat->motif_rejet }} — l’ordonnateur peut réémettre un mandat depuis l’engagement après correction.</div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="kpi"><p class="kpi-libelle">Statut</p><p class="mt-2"><x-statut :statut="$mandat->statut" /></p></div>
        <div class="kpi"><p class="kpi-libelle">Montant</p><p class="kpi-valeur text-2xl">{{ fcfa($mandat->montant) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Émis le</p><p class="kpi-valeur text-2xl">{{ date_fr($mandat->date) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Payé le</p><p class="kpi-valeur text-2xl">{{ $mandat->date_paiement ? date_fr($mandat->date_paiement) : '—' }}</p></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="carte carte-corps text-sm">
            <h2 class="mb-3 text-base">Imputation et pièces</h2>
            <dl class="grid grid-cols-3 gap-y-2">
                <dt class="text-slate-500">Programme</dt><dd class="col-span-2">{{ $l->action->programme->intitule }}</dd>
                <dt class="text-slate-500">Action</dt><dd class="col-span-2">{{ $l->action->intitule }}</dd>
                <dt class="text-slate-500">Nature</dt><dd class="col-span-2">{{ $l->nature->intitule }}</dd>
                <dt class="text-slate-500">Service</dt><dd class="col-span-2">{{ $l->service->intitule }}</dd>
                <dt class="text-slate-500">Engagement</dt><dd class="col-span-2"><a href="{{ route('engagements.show', $e) }}" class="lien">{{ $e->numero }}</a> ({{ fcfa($e->montant) }})</dd>
                <dt class="text-slate-500">Liquidation</dt><dd class="col-span-2">{{ $mandat->liquidation->numero }} · {{ $mandat->liquidation->reference_facture ?: 'sans référence' }}</dd>
                <dt class="text-slate-500">Compte débité</dt><dd class="col-span-2">{{ $l->nature->compte?->numero }} {{ $l->nature->compte?->libelle }}</dd>
            </dl>
        </div>
        <div class="space-y-6">
            <div class="carte carte-corps text-sm">
                <h2 class="mb-3 text-base">Comptabilité</h2>
                <p>Prise en charge : @if ($mandat->ecriture)<a href="{{ route('ecritures.show', $mandat->ecriture) }}" class="lien">{{ $mandat->ecriture->numero_piece }}</a> ({{ $mandat->pris_en_charge_le?->format('d/m/Y') }})@else — @endif</p>
                <p class="mt-1">Paiement : @if ($mandat->paiement?->ecriture)<a href="{{ route('ecritures.show', $mandat->paiement->ecriture) }}" class="lien">{{ $mandat->paiement->ecriture->numero_piece }}</a> · {{ $mandat->paiement->compteTresorerie->nom }} · {{ \App\Models\MouvementTresorerie::MODES[$mandat->paiement->mode] ?? '' }} {{ $mandat->paiement->reference }}@else — @endif</p>
                @if ($u->estComptable() && $mandat->paiement)
                    <form method="POST" action="{{ route('mouvements.destroy', $mandat->paiement) }}" class="mt-3" data-confirm="Annuler le paiement ? L’écriture sera contre-passée et le mandat redeviendra « pris en charge ».">@csrf @method('DELETE')<button class="btn-danger btn-petit">Annuler le paiement</button></form>
                @endif
            </div>
            @if ($u->estComptable() && $mandat->statut === 'emis')
                <form method="POST" action="{{ route('mandats.rejeter', $mandat) }}" class="carte carte-corps space-y-3">
                    @csrf
                    <h2 class="text-base">Rejeter le mandat</h2>
                    <x-champ nom="motif" label="Motif du rejet" type="textarea" requis />
                    <button class="btn-danger">Rejeter</button>
                </form>
            @endif
        </div>
    </div>
</x-layout>
