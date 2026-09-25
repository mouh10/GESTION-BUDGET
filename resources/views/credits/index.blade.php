<x-layout titre="Crédits">
    <x-entete titre="Crédits budgétaires" :sous-titre="$exercice->libelle.' · autorisations d’engagement (AE) et crédits de paiement (CP) par ligne'">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('credits.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouvelle ligne</a>
        @endif
    </x-entete>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([['AE révisées', 'ae_revisee'], ['CP révisés', 'cp_revise'], ['Engagé', 'engage'], ['Payé', 'paye'], ['CP disponibles', 'cp_disponible']] as [$lib, $cle])
            <div class="kpi p-4"><p class="text-sm text-slate-500">{{ $lib }}</p><p class="titre mt-1 text-xl font-semibold tabular-nums">{{ montant($totaux[$cle]) }}</p></div>
        @endforeach
    </div>

    <form method="GET" class="carte carte-corps mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div>
            <label class="etiquette" for="programme_id">Programme</label>
            <select id="programme_id" name="programme_id" class="champ" data-auto-submit>
                <option value="">Tous</option>
                @foreach ($programmes as $p)<option value="{{ $p->id }}" @selected(request('programme_id') == $p->id)>{{ $p->code }} - {{ $p->libelle }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="service_id">Service</label>
            <select id="service_id" name="service_id" class="champ" data-auto-submit>
                <option value="">Tous</option>
                @foreach ($services as $s)<option value="{{ $s->id }}" @selected(request('service_id') == $s->id)>{{ $s->code }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="titre">Titre</label>
            <select id="titre" name="titre" class="champ" data-auto-submit>
                <option value="">Tous</option>
                @foreach (\App\Models\Nature::TITRES_DEPENSE as $v => $l)<option value="{{ $v }}" @selected(request('titre') == $v)>T{{ $v }} — {{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="source">Source</label>
            <select id="source" name="source" class="champ" data-auto-submit>
                <option value="">Toutes</option>
                @foreach (\App\Models\LigneCredit::SOURCES as $v => $l)<option value="{{ $v }}" @selected(request('source') === $v)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <a href="{{ route('credits.index') }}" class="btn-secondaire">Réinitialiser</a>
    </form>

    <div class="space-y-5">
        @forelse ($groupes as $g)
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h2 class="text-base">Programme {{ $g->libelle }}</h2>
                    <div class="w-56"><x-barre :taux="$g->totaux['taux_engagement']" /></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Imputation</th><th>Nature</th>
                                <th class="num">AE révisées</th><th class="num">CP révisés</th><th class="num">Engagé</th>
                                <th class="num">Ordonnancé</th><th class="num">AE dispo.</th><th class="num">CP dispo.</th><th class="w-36">Engagement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($g->lignes as $r)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('credits.show', $r->ligne) }}" class="lien font-medium tabular-nums">{{ $r->programme->code }}.{{ $r->action->code }} · {{ $r->nature->code }}</a>
                                        <span class="block text-xs text-slate-500">{{ $r->service->code }} · {{ \App\Models\LigneCredit::SOURCES[$r->ligne->source] }}</span>
                                    </td>
                                    <td>{{ $r->ligne->libelle ?: $r->nature->libelle }}<span class="block text-xs text-slate-500">T{{ $r->titre }}</span></td>
                                    <td class="num">{{ montant($r->ae_revisee) }}</td>
                                    <td class="num">{{ montant($r->cp_revise) }}</td>
                                    <td class="num">{{ montant($r->engage) }}</td>
                                    <td class="num">{{ montant($r->ordonnance) }}</td>
                                    <td class="num {{ $r->ae_disponible <= 0 ? 'font-semibold text-red-700' : '' }}">{{ montant($r->ae_disponible) }}</td>
                                    <td class="num {{ $r->cp_disponible <= 0 ? 'font-semibold text-red-700' : '' }}">{{ montant($r->cp_disponible) }}</td>
                                    <td><x-barre :taux="$r->taux_engagement" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Total programme</td>
                                <td class="num">{{ montant($g->totaux['ae_revisee']) }}</td><td class="num">{{ montant($g->totaux['cp_revise']) }}</td>
                                <td class="num">{{ montant($g->totaux['engage']) }}</td><td class="num">{{ montant($g->totaux['ordonnance']) }}</td>
                                <td class="num">{{ montant($g->totaux['ae_disponible']) }}</td><td class="num">{{ montant($g->totaux['cp_disponible']) }}</td><td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @empty
            <div class="carte carte-corps text-slate-500">Aucune ligne de crédits pour ces critères.</div>
        @endforelse
    </div>
</x-layout>
