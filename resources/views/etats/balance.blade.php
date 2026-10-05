@php $t = $balance['totaux']; $equilibre = abs($t['debit'] - $t['credit']) < 0.01; @endphp
<x-layout titre="Balance">
    <x-entete titre="Balance générale" :sous-titre="$exercice->libelle">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondaire">Exporter (CSV)</a>
    </x-entete>

    <form method="GET" class="carte carte-corps no-print mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="etiquette" for="classe">Classe</label>
            <select id="classe" name="classe" class="champ">
                <option value="">Toutes</option>
                @foreach (\App\Models\Compte::CLASSES as $n => $lib)
                    <option value="{{ $n }}" @selected(request('classe') == $n)>{{ $n }} - {{ $lib }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ"></div>
        <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Afficher</button>
    </form>

    @unless (request('classe'))
        <div class="mb-4 rounded-lg px-4 py-3 text-sm {{ $equilibre ? 'border border-emerald-200 bg-emerald-50 text-emerald-800' : 'border border-red-200 bg-red-50 text-red-800' }}">
            {{ $equilibre ? 'La balance est équilibrée : total des débits = total des crédits.' : 'Attention : la balance n’est pas équilibrée.' }}
        </div>
    @endunless

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead>
                <tr><th rowspan="2">Compte</th><th rowspan="2">Intitulé</th><th colspan="2" class="text-center!">Mouvements</th><th colspan="2" class="text-center!">Soldes</th></tr>
                <tr><th class="num">Débit</th><th class="num">Crédit</th><th class="num">Débiteur</th><th class="num">Créditeur</th></tr>
            </thead>
            <tbody>
                @php $classeCourante = null; @endphp
                @forelse ($balance['lignes'] as $l)
                    @if ($classeCourante !== $l->classe)
                        @php $classeCourante = $l->classe; @endphp
                        <tr><td colspan="6" class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">Classe {{ $l->classe }} · {{ \App\Models\Compte::CLASSES[$l->classe] ?? '' }}</td></tr>
                    @endif
                    <tr>
                        <td class="tabular-nums"><a href="{{ route('etats.grand-livre', ['compte_du' => $l->numero, 'compte_au' => $l->numero, 'du' => request('du'), 'au' => request('au')]) }}" class="lien">{{ $l->numero }}</a></td>
                        <td>{{ $l->libelle }}</td>
                        <td class="num">{{ montant($l->debit) }}</td>
                        <td class="num">{{ montant($l->credit) }}</td>
                        <td class="num">{{ $l->solde_debiteur > 0 ? montant($l->solde_debiteur) : '' }}</td>
                        <td class="num">{{ $l->solde_crediteur > 0 ? montant($l->solde_crediteur) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Totaux</td>
                    <td class="num">{{ montant($t['debit']) }}</td>
                    <td class="num">{{ montant($t['credit']) }}</td>
                    <td class="num">{{ montant($t['solde_debiteur']) }}</td>
                    <td class="num">{{ montant($t['solde_crediteur']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-layout>
