<x-layout titre="Grand livre">
    <x-entete titre="Grand livre" :sous-titre="$exercice->libelle.' · détail des mouvements par compte'">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondaire">Exporter (CSV)</a>
    </x-entete>

    <form method="GET" class="carte carte-corps no-print mb-4 flex flex-wrap items-end gap-3">
        <div class="w-32"><label class="etiquette" for="compte_du">Du compte</label><input id="compte_du" name="compte_du" value="{{ request('compte_du') }}" class="champ" placeholder="ex. 4"></div>
        <div class="w-32"><label class="etiquette" for="compte_au">Au compte</label><input id="compte_au" name="compte_au" value="{{ request('compte_au') }}" class="champ" placeholder="ex. 4999"></div>
        <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ"></div>
        <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Afficher</button>
        <button type="button" class="btn-secondaire" onclick="window.print()">Imprimer</button>
    </form>

    @forelse ($grandLivre as $g)
        <div class="carte mb-4 overflow-x-auto">
            <div class="carte-entete">
                <h2 class="text-base">{{ $g->compte->numero }} · {{ $g->compte->libelle }}</h2>
                <span class="text-sm tabular-nums {{ $g->solde < 0 ? 'text-red-700' : '' }}">
                    Solde {{ $g->solde >= 0 ? 'débiteur' : 'créditeur' }} : <strong>{{ montant(abs($g->solde)) }}</strong>
                </span>
            </div>
            <table class="tableau">
                <thead><tr><th class="w-24">Date</th><th class="w-32">Pièce</th><th class="w-12">Jnl</th><th>Libellé</th><th class="num w-32">Débit</th><th class="num w-32">Crédit</th><th class="num w-36">Solde</th></tr></thead>
                <tbody>
                    @if ($g->report_debit || $g->report_credit)
                        <tr class="italic text-slate-500">
                            <td colspan="4">Report au {{ date_fr(request('du')) }}</td>
                            <td class="num">{{ montant($g->report_debit) }}</td>
                            <td class="num">{{ montant($g->report_credit) }}</td>
                            <td class="num">{{ montant($g->report_debit - $g->report_credit) }}</td>
                        </tr>
                    @endif
                    @foreach ($g->mouvements as $m)
                        <tr>
                            <td class="whitespace-nowrap">{{ date_fr($m->date) }}</td>
                            <td class="whitespace-nowrap"><a href="{{ route('ecritures.show', $m->ecriture_id) }}" class="lien">{{ $m->numero_piece }}</a></td>
                            <td>{{ $m->journal }}</td>
                            <td>{{ $m->libelle ?: $m->ecriture_libelle }}@if ($m->tiers)<span class="text-slate-500"> · {{ $m->tiers }}</span>@endif</td>
                            <td class="num">{{ $m->debit > 0 ? montant($m->debit) : '' }}</td>
                            <td class="num">{{ $m->credit > 0 ? montant($m->credit) : '' }}</td>
                            <td class="num {{ $m->solde < 0 ? 'text-red-700' : '' }}">{{ montant($m->solde) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="4">Total du compte</td><td class="num">{{ montant($g->total_debit) }}</td><td class="num">{{ montant($g->total_credit) }}</td><td class="num">{{ montant($g->solde) }}</td></tr>
                </tfoot>
            </table>
        </div>
    @empty
        <div class="carte carte-corps text-slate-500">Aucun mouvement pour ces critères.</div>
    @endforelse
</x-layout>
