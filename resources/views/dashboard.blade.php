<x-layout titre="Tableau de bord">
    @if (! $exercice)
        <div class="carte carte-corps text-center">
            <h1>Bienvenue dans {{ config('app.name') }}</h1>
            <p class="mt-2 text-slate-600">Commencez par créer l’exercice budgétaire (gestion).</p>
            @if (auth()->user()->estAdmin())
                <a href="{{ route('exercices.create') }}" class="btn-primaire mt-4">Créer un exercice</a>
            @endif
        </div>
    @else
        @php
            $u = auth()->user();
            $prenom = explode(' ', trim($u->name))[0];
            $t = $totaux;
            $taux = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : null;
        @endphp

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1>Bonjour, {{ $prenom }}</h1>
                <p class="mt-1 text-lg text-slate-500">
                    {{ config('gestion.entreprise.nom') }} — {{ ucfirst(now()->translatedFormat('l j F Y')) }}
                    · <span class="{{ $exercice->cloture ? 'text-amber-700' : '' }}">{{ $exercice->libelle }}{{ $exercice->cloture ? ' (clôturée)' : '' }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                @if ($u->estOrdonnateur())
                    <a href="{{ route('engagements.create') }}" class="btn-primaire px-5 py-3 text-base"><x-icone nom="plus" class="h-5 w-5" /> Nouvel engagement</a>
                @endif
                <a href="{{ route('execution.depenses') }}" class="btn-secondaire px-5 py-3 text-base"><x-icone nom="camembert" class="h-5 w-5 text-slate-500" /> État d’exécution</a>
            </div>
        </div>

        {{-- Indicateurs --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="kpi">
                <div class="flex items-start justify-between">
                    <p class="kpi-libelle">Crédits ouverts (CP)</p>
                    <span class="kpi-icone bg-marque-50 text-marque-600"><x-icone nom="cible" class="h-5 w-5" /></span>
                </div>
                <p class="kpi-valeur">{{ montant($t['cp_revise']) }} F</p>
                <p class="mt-2 text-sm text-slate-500">AE : {{ montant($t['ae_revisee']) }} F @if ($t['cp_gele'] > 0)· gel {{ montant($t['cp_gele']) }}@endif</p>
            </div>
            <div class="kpi">
                <div class="flex items-start justify-between">
                    <p class="kpi-libelle">Engagé</p>
                    <span class="kpi-icone bg-amber-50 text-amber-600"><x-icone nom="stylo" class="h-5 w-5" /></span>
                </div>
                <p class="kpi-valeur">{{ montant($t['engage']) }} F</p>
                <div class="mt-2"><x-barre :taux="$t['taux_engagement']" couleur="bg-amber-500" /></div>
            </div>
            <div class="kpi">
                <div class="flex items-start justify-between">
                    <p class="kpi-libelle">Ordonnancé (mandaté)</p>
                    <span class="kpi-icone bg-violet-50 text-violet-600"><x-icone nom="recu" class="h-5 w-5" /></span>
                </div>
                <p class="kpi-valeur">{{ montant($t['ordonnance']) }} F</p>
                <div class="mt-2"><x-barre :taux="$t['taux_ordonnancement']" couleur="bg-violet-500" /></div>
            </div>
            <div class="kpi">
                <div class="flex items-start justify-between">
                    <p class="kpi-libelle">Payé</p>
                    <span class="kpi-icone bg-emerald-50 text-emerald-600"><x-icone nom="portefeuille" class="h-5 w-5" /></span>
                </div>
                <p class="kpi-valeur">{{ montant($t['paye']) }} F</p>
                <div class="mt-2"><x-barre :taux="$t['taux_paiement']" couleur="bg-emerald-500" /></div>
            </div>
        </div>

        {{-- À traiter --}}
        @php
            $at = $aTraiter;
            $cartes = [
                ['Engagements à viser', $at['aViser'], route('engagements.index', ['statut' => 'soumis']), 'bouclier', 'text-amber-600 bg-amber-50', $u->estControleur()],
                ['Mandats à prendre en charge', $at['aPrendreEnCharge'], route('mandats.index', ['statut' => 'emis']), 'recu', 'text-marque-600 bg-marque-50', $u->estComptable()],
                ['Mandats à payer', $at['aPayer'], route('mandats.index', ['statut' => 'pris_en_charge']), 'portefeuille', 'text-emerald-600 bg-emerald-50', $u->estComptable()],
                ['Engagements en brouillon / rejetés', $at['brouillons'], route('engagements.index', ['statut' => 'brouillon']), 'stylo', 'text-slate-600 bg-slate-100', $u->estOrdonnateur()],
            ];
        @endphp
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cartes as [$lib, $v, $url, $icone, $couleur, $moi])
                <a href="{{ $url }}" class="carte flex items-center gap-4 p-4 transition hover:border-marque-400 {{ $moi && $v->n > 0 ? 'ring-2 ring-marque-500/30' : '' }}">
                    <span class="kpi-icone h-11 w-11 {{ $couleur }}"><x-icone :nom="$icone" class="h-5 w-5" /></span>
                    <span class="min-w-0">
                        <span class="block text-sm text-slate-500">{{ $lib }}</span>
                        <span class="titre block text-lg font-semibold text-slate-900">{{ $v->n }} <span class="text-sm font-normal text-slate-500">· {{ montant($v->total) }} F</span></span>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Graphique et programmes --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-5">
            <div class="carte xl:col-span-3">
                <div class="px-6 pt-6">
                    <h2 class="text-xl">Engagements et paiements</h2>
                    <p class="sous-titre mt-0.5">Montants par mois sur la gestion {{ $exercice->annee() }}</p>
                </div>
                <div class="p-6 pt-4">
                    <div class="h-80"><canvas data-graphique='@json($evolution)' aria-label="Graphique des engagements et paiements mensuels" role="img"></canvas></div>
                </div>
            </div>

            <div class="carte xl:col-span-2">
                <div class="flex items-start justify-between px-6 pt-6">
                    <div>
                        <h2 class="text-xl">Exécution par programme</h2>
                        <p class="sous-titre mt-0.5">Engagé sur AE révisées</p>
                    </div>
                    <a href="{{ route('execution.depenses', ['par' => 'programme']) }}" class="lien text-sm">Détail</a>
                </div>
                <ul class="mt-3 divide-y divide-slate-100 px-6 pb-3">
                    @forelse ($programmes as $i => $p)
                        <li class="py-3.5">
                            <div class="flex items-center gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-marque-50 text-sm font-semibold text-marque-700">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium uppercase text-slate-900" title="{{ $p->libelle }}">{{ $p->libelle }}</p>
                                    <p class="text-sm text-slate-500">{{ montant($p->totaux['engage']) }} / {{ montant($p->totaux['ae_revisee']) }} F</p>
                                </div>
                            </div>
                            <div class="mt-2 pl-15"><x-barre :taux="$p->totaux['taux_engagement']" /></div>
                        </li>
                    @empty
                        <li class="py-6 text-sm text-slate-500">Aucun crédit inscrit.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            {{-- Par titre --}}
            <div class="carte">
                <div class="px-6 pt-6">
                    <h2 class="text-xl">Exécution par titre</h2>
                    <p class="sous-titre mt-0.5">Classification économique</p>
                </div>
                <ul class="mt-3 space-y-4 px-6 pb-6">
                    @forelse ($titres as $g)
                        <li>
                            <div class="flex justify-between gap-2 text-sm">
                                <span class="truncate font-medium text-slate-700" title="{{ $g->libelle }}">{{ $g->libelle }}</span>
                                <span class="whitespace-nowrap tabular-nums text-slate-500">{{ montant($g->totaux['ordonnance']) }} F</span>
                            </div>
                            <div class="mt-1.5"><x-barre :taux="$g->totaux['taux_ordonnancement']" couleur="bg-violet-500" /></div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Aucun crédit.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Recettes --}}
            <div class="carte">
                <div class="flex items-start justify-between px-6 pt-6">
                    <div>
                        <h2 class="text-xl">Recettes</h2>
                        <p class="sous-titre mt-0.5">Prévu : {{ fcfa($recettes['prevu']) }}</p>
                    </div>
                    <a href="{{ route('execution.recettes') }}" class="lien text-sm">Détail</a>
                </div>
                <div class="space-y-5 p-6">
                    @foreach ([['Émis (titres pris en charge)', $recettes['emis'], 'bg-marque-500'], ['Recouvré', $recettes['recouvre'], 'bg-emerald-500']] as [$lib, $v, $c])
                        <div>
                            <div class="flex justify-between text-sm"><span class="font-medium text-slate-700">{{ $lib }}</span><span class="font-semibold tabular-nums">{{ montant($v) }} F</span></div>
                            <div class="mt-2"><x-barre :taux="$taux($v, $recettes['prevu'])" :couleur="$c" /></div>
                        </div>
                    @endforeach
                    <p class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                        {{ $at['titres']->n }} titre(s) restant à recouvrer · <strong>{{ fcfa($at['titres']->total) }}</strong>
                    </p>
                </div>
            </div>

            {{-- Alertes --}}
            <div class="carte">
                <div class="px-6 pt-6">
                    <h2 class="text-xl">Lignes en tension</h2>
                    <p class="sous-titre mt-0.5">Taux d’engagement ≥ {{ config('gestion.alerte_budget') }} %</p>
                </div>
                <ul class="mt-3 divide-y divide-slate-100 px-6 pb-3">
                    @forelse ($alertes as $a)
                        <li class="py-3">
                            <a href="{{ route('credits.show', $a->ligne) }}" class="block">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ $a->nature->code }} · {{ $a->nature->libelle }}</span>
                                <span class="block text-xs text-slate-500">{{ $a->ligne->imputation() }} · disponible {{ fcfa($a->ae_disponible) }}</span>
                            </a>
                            <div class="mt-1.5"><x-barre :taux="$a->taux_engagement" /></div>
                        </li>
                    @empty
                        <li class="py-6 text-sm text-emerald-700">Aucune ligne proche de l’épuisement.</li>
                    @endforelse
                </ul>
                @if ($at['actes'] > 0)
                    <a href="{{ route('modifications.index') }}" class="mx-6 mb-5 block rounded-xl bg-violet-50 px-3 py-2 text-sm text-violet-700">{{ $at['actes'] }} acte(s) de modification en attente d’approbation</a>
                @endif
            </div>
        </div>

        {{-- Derniers engagements --}}
        <div class="carte mt-6 overflow-hidden">
            <div class="flex items-start justify-between px-6 py-5">
                <div>
                    <h2 class="text-xl">Derniers engagements</h2>
                    <p class="sous-titre mt-0.5">Saisis par les services gestionnaires</p>
                </div>
                <a href="{{ route('engagements.index') }}" class="lien text-sm">Tous les engagements</a>
            </div>
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>N°</th><th>Date</th><th>Bénéficiaire</th><th>Objet</th><th>Imputation</th><th class="num">Montant</th><th>Statut</th></tr></thead>
                    <tbody>
                        @forelse ($derniersEngagements as $e)
                            <tr>
                                <td class="whitespace-nowrap"><a href="{{ route('engagements.show', $e) }}" class="lien font-medium">{{ $e->numero }}</a></td>
                                <td class="whitespace-nowrap">{{ date_fr($e->date) }}</td>
                                <td>{{ $e->tiers->nom }}</td>
                                <td>{{ $e->objet }}</td>
                                <td class="whitespace-nowrap text-xs text-slate-500">{{ $e->ligneCredit->action->programme->code }} · {{ $e->ligneCredit->nature->code }}</td>
                                <td class="num">{{ montant($e->montant) }}</td>
                                <td><x-statut :statut="$e->statut" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-slate-500">Aucun engagement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-layout>
