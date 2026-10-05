@php $ecart = round($bilan['totalActif'] - $bilan['totalPassif'], 2); @endphp
<x-layout titre="Bilan">
    <x-entete titre="Bilan" :sous-titre="$exercice->libelle.' · situation au '.date_fr(request('au') ?: $exercice->date_fin)" />

    <form method="GET" class="carte carte-corps no-print mb-4 flex flex-wrap items-end gap-3">
        <div><label class="etiquette" for="au">Situation au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Afficher</button>
    </form>

    @if (abs($ecart) >= 0.01)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Écart actif / passif : {{ fcfa($ecart) }}. Vérifiez la balance.</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach (['ACTIF' => $bilan['actif'], 'PASSIF' => $bilan['passif']] as $cote => $masses)
            <div class="carte overflow-x-auto">
                <div class="carte-entete"><h2 class="text-base">{{ $cote }}</h2></div>
                <table class="tableau">
                    <tbody>
                        @foreach ($masses as $titre => $lignes)
                            <tr><td colspan="2" class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $titre }}</td></tr>
                            @forelse ($lignes as $l)
                                <tr><td><span class="tabular-nums text-slate-500">{{ $l->numero }}</span> {{ $l->libelle }}</td><td class="num">{{ montant($l->montant) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-slate-400">—</td></tr>
                            @endforelse
                            @if ($cote === 'PASSIF' && $loop->first)
                                <tr><td><span class="tabular-nums text-slate-500">13</span> Résultat net de l'exercice (en cours)</td><td class="num {{ $bilan['resultat'] < 0 ? 'text-red-700' : '' }}">{{ montant($bilan['resultat']) }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td>TOTAL {{ $cote }}</td><td class="num">{{ montant($cote === 'ACTIF' ? $bilan['totalActif'] : $bilan['totalPassif']) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-slate-500">Bilan simplifié établi à partir des soldes des comptes. Les amortissements (28) et dépréciations (29, 39) apparaissent en déduction de l’actif.</p>
</x-layout>
