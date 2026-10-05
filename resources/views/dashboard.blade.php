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
            $t = $totaux;
            $at = $aTraiter;
            $pct = fn ($v) => $v !== null ? montant($v, 1).' %' : '—';
        @endphp

        <x-entete titre="Tableau de bord" :sous-titre="$exercice->libelle.' · '.config('gestion.entreprise.nom').' · '.ucfirst(now()->translatedFormat('l j F Y'))">
            @if ($u->estOrdonnateur())
                <a href="{{ route('engagements.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouvel engagement</a>
            @endif
        </x-entete>

        {{-- Trois grandes cartes --}}
        <div class="grid gap-5 lg:grid-cols-3">
            @foreach ([
                ['bleu', 'Engagements', $t['engage'], 'sur '.montant($t['ae_revisee']).' F d’AE · '.$pct($t['taux_engagement']), 'stylo', $evolution['engage'], route('engagements.index')],
                ['vert', 'Recettes recouvrées', $recettes['recouvre'], 'sur '.montant($recettes['prevu']).' F prévus', 'hausse', $evolution['recouvre'], route('titres.index')],
                ['rouge', 'Paiements', $t['paye'], 'sur '.montant($t['cp_revise']).' F de CP · '.$pct($t['taux_paiement']), 'portefeuille', $evolution['paye'], route('mandats.index', ['statut' => 'paye'])],
            ] as [$couleur, $titre, $valeur, $detail, $icone, $serie, $lien])
                <a href="{{ $lien }}" class="carte-vive {{ $couleur }} block transition hover:brightness-105">
                    <div class="flex items-center gap-3">
                        <span class="icone-ronde"><x-icone :nom="$icone" class="h-5 w-5" /></span>
                        <div class="min-w-0">
                            <p class="text-sm text-white/85">{{ $titre }}</p>
                            <p class="text-2xl font-bold tabular-nums">{{ montant($valeur) }} F</p>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-white/75">{{ $detail }}</p>
                    <div class="mt-3 h-28"><canvas data-courbe='@json($serie)' aria-label="Évolution mensuelle : {{ $titre }}" role="img"></canvas></div>
                </a>
            @endforeach
        </div>

        {{-- Dossiers à traiter --}}
        <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Engagements à viser', $at['aViser'], route('engagements.index', ['statut' => 'soumis']), $u->estControleur()],
                ['Mandats à prendre en charge', $at['aPrendreEnCharge'], route('mandats.index', ['statut' => 'emis']), $u->estComptable()],
                ['Mandats à payer', $at['aPayer'], route('mandats.index', ['statut' => 'pris_en_charge']), $u->estComptable()],
                ['Titres à recouvrer', $at['titres'], route('titres.index', ['statut' => 'emis']), $u->estComptable()],
            ] as [$lib, $v, $url, $moi])
                <a href="{{ $url }}" class="carte block p-4 transition hover:border-marque-200 hover:shadow">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium text-slate-700">{{ $lib }}</p>
                        <span class="rounded-md px-2 py-0.5 text-xs font-bold {{ $v->n > 0 && $moi ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500' }}">{{ $v->n }}</span>
                    </div>
                    <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">{{ montant($v->total) }} <span class="text-sm font-normal text-slate-400">FCFA</span></p>
                </a>
            @endforeach
        </div>

        {{-- Analyse --}}
        <div class="mt-9" data-onglets-locaux>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-xl font-bold">Analyse</h2>
                <a href="{{ route('execution.depenses') }}" class="lien text-sm">Situation d’exécution complète</a>
            </div>
            <div class="onglets mb-5">
                <button type="button" class="onglet actif" data-onglet="depenses">Dépenses</button>
                <button type="button" class="onglet" data-onglet="programmes">Par programme</button>
                <button type="button" class="onglet" data-onglet="recettes">Recettes</button>
            </div>

            <div data-panneau="depenses">
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([['Crédits ouverts (CP)', montant($t['cp_revise']).' F', 'AE : '.montant($t['ae_revisee']).' F'], ['Taux d’engagement', $pct($t['taux_engagement']), montant($t['engage']).' F engagés'], ['Taux d’ordonnancement', $pct($t['taux_ordonnancement']), montant($t['ordonnance']).' F mandatés'], ['CP disponibles', montant($t['cp_disponible']).' F', 'restes à payer : '.montant($t['ordonnance'] - $t['paye']).' F']] as [$lib, $val, $sous])
                        <div class="carte p-4">
                            <p class="text-sm font-medium text-slate-700">{{ $lib }}</p>
                            <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">{{ $val }}</p>
                            <p class="text-xs text-slate-500">{{ $sous }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="carte mt-5 overflow-x-auto">
                    <table class="tableau">
                        <thead><tr><th>Titre</th><th class="num">CP révisés</th><th class="num">Engagé</th><th class="num">Payé</th><th class="w-48">Exécution (payé / CP)</th></tr></thead>
                        <tbody>
                            @foreach ($titres as $g)
                                <tr>
                                    <td>{{ $g->libelle }}</td>
                                    <td class="num">{{ montant($g->totaux['cp_revise']) }}</td>
                                    <td class="num">{{ montant($g->totaux['engage']) }}</td>
                                    <td class="num">{{ montant($g->totaux['paye']) }}</td>
                                    <td><x-barre :taux="$g->totaux['taux_paiement']" couleur="bg-marque-500" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div data-panneau="programmes" hidden>
                <div class="carte overflow-x-auto">
                    <table class="tableau">
                        <thead><tr><th>Programme</th><th class="num">AE révisées</th><th class="num">Engagé</th><th class="num">CP révisés</th><th class="num">Payé</th><th class="w-48">Engagement</th></tr></thead>
                        <tbody>
                            @foreach ($programmes as $p)
                                <tr>
                                    <td class="font-medium text-slate-800">{{ $p->libelle }}</td>
                                    <td class="num">{{ montant($p->totaux['ae_revisee']) }}</td>
                                    <td class="num">{{ montant($p->totaux['engage']) }}</td>
                                    <td class="num">{{ montant($p->totaux['cp_revise']) }}</td>
                                    <td class="num">{{ montant($p->totaux['paye']) }}</td>
                                    <td><x-barre :taux="$p->totaux['taux_engagement']" couleur="bg-marque-500" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($alertes->isNotEmpty())
                    <p class="mt-4 text-sm text-slate-600"><strong class="text-amber-700">{{ $alertes->count() }} ligne(s) en tension</strong> (engagées à plus de {{ config('gestion.alerte_budget') }} %) :
                        @foreach ($alertes as $a)<a href="{{ route('credits.show', $a->ligne) }}" class="lien">{{ $a->nature->libelle }}</a>@if (! $loop->last), @endif @endforeach
                    </p>
                @endif
            </div>

            <div data-panneau="recettes" hidden>
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([['Prévisions', $recettes['prevu']], ['Titres émis', $recettes['emis']], ['Recouvré', $recettes['recouvre']], ['Reste à recouvrer', $recettes['emis'] - $recettes['recouvre']]] as [$lib, $val])
                        <div class="carte p-4">
                            <p class="text-sm font-medium text-slate-700">{{ $lib }}</p>
                            <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">{{ montant($val) }} <span class="text-sm font-normal text-slate-400">FCFA</span></p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm"><a href="{{ route('execution.recettes') }}" class="lien">Voir le détail par nature de recette</a></p>
            </div>
        </div>

        {{-- Derniers engagements --}}
        <div class="mt-9">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="text-xl font-bold">Derniers engagements</h2>
                <a href="{{ route('engagements.index') }}" class="lien text-sm">Tout voir</a>
            </div>
            <div class="carte overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>N°</th><th>Date</th><th>Bénéficiaire</th><th>Objet</th><th class="num">Montant</th><th>Statut</th></tr></thead>
                    <tbody>
                        @forelse ($derniersEngagements as $e)
                            <tr>
                                <td class="whitespace-nowrap"><a href="{{ route('engagements.show', $e) }}" class="lien font-medium">{{ $e->numero }}</a></td>
                                <td class="whitespace-nowrap">{{ date_fr($e->date) }}</td>
                                <td>{{ $e->tiers->nom }}</td>
                                <td>{{ $e->objet }}</td>
                                <td class="num">{{ montant($e->montant) }}</td>
                                <td><x-statut :statut="$e->statut" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-slate-500">Aucun engagement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-layout>
