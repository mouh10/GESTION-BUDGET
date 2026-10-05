@php
    $vues = ['programme' => 'Programme', 'action' => 'Action', 'titre' => 'Titre', 'service' => 'Service', 'source' => 'Source', 'ligne' => 'Détail par ligne'];
    $cols = [['cp_initial', 'CP initiaux'], ['cp_modif', 'Modif.'], ['cp_revise', 'CP révisés'], ['engage', 'Engagé'], ['ordonnance', 'Ordonnancé'], ['paye', 'Payé'], ['cp_disponible', 'CP dispo.']];
@endphp
<x-layout titre="Exécution des dépenses">
    <x-entete titre="Situation d’exécution des dépenses" :sous-titre="$exercice->libelle.' · situation '.(request('au') ? 'au '.date_fr(request('au')) : 'à ce jour')">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondaire">Exporter (CSV)</a>
    </x-entete>

    <div class="no-print mb-4 inline-flex flex-wrap rounded-xl border border-slate-200 bg-white p-1 text-sm">
        @foreach ($vues as $v => $l)
            <a href="{{ request()->fullUrlWithQuery(['par' => $v]) }}" class="rounded-lg px-3 py-1.5 {{ $par === $v ? 'bg-nuit text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $l }}</a>
        @endforeach
    </div>

    <form method="GET" class="carte carte-corps no-print mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        <input type="hidden" name="par" value="{{ $par }}">
        <div class="lg:col-span-2"><label class="etiquette" for="programme_id">Programme</label><select id="programme_id" name="programme_id" class="champ"><option value="">Tous</option>@foreach ($programmes as $p)<option value="{{ $p->id }}" @selected(request('programme_id') == $p->id)>{{ $p->code }} - {{ $p->libelle }}</option>@endforeach</select></div>
        <div><label class="etiquette" for="service_id">Service</label><select id="service_id" name="service_id" class="champ"><option value="">Tous</option>@foreach ($services as $s)<option value="{{ $s->id }}" @selected(request('service_id') == $s->id)>{{ $s->code }}</option>@endforeach</select></div>
        <div><label class="etiquette" for="titre">Titre</label><select id="titre" name="titre" class="champ"><option value="">Tous</option>@foreach (\App\Models\Nature::TITRES_DEPENSE as $v => $l)<option value="{{ $v }}" @selected(request('titre') == $v)>Titre {{ $v }}</option>@endforeach</select></div>
        <div><label class="etiquette" for="au">Situation au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Afficher</button>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="kpi p-4"><p class="text-sm text-slate-500">Taux d’engagement (AE)</p><p class="titre mt-1 text-2xl font-semibold">{{ $totaux['taux_engagement'] !== null ? montant($totaux['taux_engagement'], 1).' %' : '—' }}</p><p class="text-xs text-slate-500">{{ montant($totaux['engage']) }} / {{ montant($totaux['ae_revisee']) }}</p></div>
        <div class="kpi p-4"><p class="text-sm text-slate-500">Taux d’ordonnancement (CP)</p><p class="titre mt-1 text-2xl font-semibold">{{ $totaux['taux_ordonnancement'] !== null ? montant($totaux['taux_ordonnancement'], 1).' %' : '—' }}</p><p class="text-xs text-slate-500">{{ montant($totaux['ordonnance']) }} / {{ montant($totaux['cp_revise']) }}</p></div>
        <div class="kpi p-4"><p class="text-sm text-slate-500">Taux de paiement (CP)</p><p class="titre mt-1 text-2xl font-semibold">{{ $totaux['taux_paiement'] !== null ? montant($totaux['taux_paiement'], 1).' %' : '—' }}</p><p class="text-xs text-slate-500">{{ montant($totaux['paye']) }} payés</p></div>
        <div class="kpi p-4"><p class="text-sm text-slate-500">Restes à payer</p><p class="titre mt-1 text-2xl font-semibold">{{ montant($totaux['ordonnance'] - $totaux['paye']) }}</p><p class="text-xs text-slate-500">mandats émis non payés</p></div>
    </div>

    <div class="carte overflow-x-auto">
        <table class="tableau text-[13px]">
            <thead>
                <tr><th class="min-w-64">{{ $vues[$par] }}</th>@foreach ($cols as [$c, $l])<th class="num">{{ $l }}</th>@endforeach<th class="w-32">Exécution</th></tr>
            </thead>
            <tbody>
                @if ($par === 'ligne')
                    @forelse ($situation as $r)
                        <tr>
                            <td class="whitespace-nowrap"><a href="{{ route('credits.show', $r->ligne) }}" class="lien font-medium">{{ $r->ligne->imputation() }}</a><span class="block max-w-64 truncate text-xs text-slate-500">{{ $r->ligne->libelle ?: $r->nature->libelle }}</span></td>
                            @foreach ($cols as [$c, $l])<td class="num {{ $c === 'cp_disponible' && $r->$c <= 0 ? 'text-red-700' : '' }}">{{ montant($r->$c) }}</td>@endforeach
                            <td><x-barre :taux="$r->taux_ordonnancement" couleur="bg-violet-500" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-slate-500">Aucune donnée.</td></tr>
                    @endforelse
                @else
                    @forelse ($groupes as $g)
                        <tr>
                            <td class="font-medium">{{ $g->libelle }}</td>
                            @foreach ($cols as [$c, $l])<td class="num">{{ montant($g->totaux[$c]) }}</td>@endforeach
                            <td><x-barre :taux="$g->totaux['taux_ordonnancement']" couleur="bg-violet-500" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-slate-500">Aucune donnée.</td></tr>
                    @endforelse
                @endif
            </tbody>
            <tfoot>
                <tr><td>Total</td>@foreach ($cols as [$c, $l])<td class="num">{{ montant($totaux[$c]) }}</td>@endforeach<td><x-barre :taux="$totaux['taux_ordonnancement']" couleur="bg-violet-500" /></td></tr>
            </tfoot>
        </table>
    </div>
    <p class="mt-3 text-xs text-slate-500">Engagé = engagements soumis et visés ; ordonnancé = mandats émis, pris en charge ou payés. Les AE, les gels et les montants liquidés figurent dans l’export CSV et sur chaque ligne de crédits.</p>
</x-layout>
