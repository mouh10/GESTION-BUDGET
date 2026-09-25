@php $onglets = ['' => 'Tous', 'emis' => 'À prendre en charge', 'pris_en_charge' => 'À payer', 'paye' => 'Payés', 'rejete' => 'Rejetés']; @endphp
<x-layout titre="Mandats">
    <x-entete titre="Mandats et paiements" sous-titre="Ordonnancement par l’ordonnateur, prise en charge et paiement par le comptable public." />

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($onglets as $v => $l)
            @php $c = $v === '' ? null : ($compteurs[$v] ?? null); @endphp
            <a href="{{ route('mandats.index', array_filter(['statut' => $v, 'q' => request('q')])) }}" class="{{ request('statut', '') === $v ? 'btn-primaire' : 'btn-secondaire' }} btn-petit">
                {{ $l }} @if ($v !== '')<span class="opacity-70">({{ $c->n ?? 0 }} · {{ montant($c->total ?? 0) }})</span>@endif
            </a>
        @endforeach
    </div>

    <form method="GET" class="mb-5 flex max-w-xl gap-2">
        <input type="hidden" name="statut" value="{{ request('statut') }}">
        <input name="q" value="{{ request('q') }}" class="champ" placeholder="N° de mandat, d’engagement, bénéficiaire, objet">
        <button class="btn-secondaire">Rechercher</button>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Mandat</th><th>Date</th><th>Bénéficiaire</th><th>Objet</th><th>Imputation</th><th class="num">Montant</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($mandats as $m)
                    @php $e = $m->liquidation->engagement; @endphp
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('mandats.show', $m) }}" class="lien font-medium">{{ $m->numero }}</a><span class="block text-xs text-slate-500">{{ $e->numero }}</span></td>
                        <td class="whitespace-nowrap">{{ date_fr($m->date) }}</td>
                        <td>{{ $e->tiers->nom }}</td>
                        <td>{{ $e->objet }}</td>
                        <td class="whitespace-nowrap text-xs text-slate-600">{{ $e->ligneCredit->action->programme->code }} · {{ $e->ligneCredit->nature->code }}</td>
                        <td class="num">{{ montant($m->montant) }}</td>
                        <td><x-statut :statut="$m->statut" />@if ($m->date_paiement)<span class="block text-xs text-slate-500">le {{ date_fr($m->date_paiement) }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-slate-500">Aucun mandat.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $mandats->links() }}
    </div>
</x-layout>
