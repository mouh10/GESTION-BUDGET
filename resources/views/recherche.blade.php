<x-layout titre="Recherche">
    <x-entete titre="Recherche" :sous-titre="$q === '' ? 'Saisissez au moins 2 caractères.' : $total.' résultat(s) pour « '.$q.' »'" />

    <form method="GET" class="relative mb-6 max-w-2xl md:hidden">
        <input type="search" name="q" value="{{ $q }}" class="champ" placeholder="Rechercher…">
    </form>

    @if ($q !== '' && $total === 0)
        <div class="carte carte-corps text-slate-500">Aucun résultat.</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        @if ($resultats['engagements']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Engagements</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['engagements'] as $x)
                        <li><a href="{{ route('engagements.show', $x) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                            <span><span class="font-medium text-slate-900">{{ $x->numero }}</span><span class="block text-xs text-slate-500">{{ $x->tiers->nom.' · '.$x->objet }}</span></span>
                            <span class="flex items-center gap-3"><span class="num text-sm">{{ montant($x->montant) }}</span>@isset($x->statut)<x-statut :statut="$x->statut" />@endisset</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resultats['mandats']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Mandats</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['mandats'] as $x)
                        <li><a href="{{ route('mandats.show', $x) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                            <span><span class="font-medium text-slate-900">{{ $x->numero }}</span><span class="block text-xs text-slate-500">{{ $x->liquidation->engagement->tiers->nom }}</span></span>
                            <span class="flex items-center gap-3"><span class="num text-sm">{{ montant($x->montant) }}</span>@isset($x->statut)<x-statut :statut="$x->statut" />@endisset</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resultats['titres']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Titres de recette</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['titres'] as $x)
                        <li><a href="{{ route('titres.show', $x) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                            <span><span class="font-medium text-slate-900">{{ $x->numero }}</span><span class="block text-xs text-slate-500">{{ $x->tiers->nom.' · '.$x->objet }}</span></span>
                            <span class="flex items-center gap-3"><span class="num text-sm">{{ montant($x->montant) }}</span>@isset($x->statut)<x-statut :statut="$x->statut" />@endisset</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resultats['marches']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Marchés</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['marches'] as $x)
                        <li><a href="{{ route('marches.show', $x) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                            <span><span class="font-medium text-slate-900">{{ $x->numero }}</span><span class="block text-xs text-slate-500">{{ $x->tiers->nom.' · '.$x->objet }}</span></span>
                            <span class="flex items-center gap-3"><span class="num text-sm">{{ montant($x->montant) }}</span>@isset($x->statut)<x-statut :statut="$x->statut" />@endisset</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resultats['tiers']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Clients et fournisseurs</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['tiers'] as $t)
                        <li><a href="{{ route('tiers.show', $t) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                            <span><span class="font-medium text-slate-900">{{ $t->nom }}</span><span class="block text-xs text-slate-500">{{ $t->code }}{{ $t->telephone ? ' · '.$t->telephone : '' }}</span></span>
                            <span class="{{ $t->estRedevable() ? 'badge-bleu' : 'badge-gris' }}">{{ \App\Models\Tiers::TYPES[$t->type] }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resultats['ecritures']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Écritures</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['ecritures'] as $e)
                        <li><a href="{{ route('ecritures.show', $e) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                            <span><span class="font-medium text-slate-900">{{ $e->numero_piece }}</span><span class="block text-xs text-slate-500">{{ $e->libelle }}</span></span>
                            <span class="text-sm text-slate-500">{{ date_fr($e->date) }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resultats['comptes']->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Comptes</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($resultats['comptes'] as $c)
                        <li><a href="{{ route('etats.grand-livre', ['compte_du' => $c->numero, 'compte_au' => $c->numero]) }}" class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50">
                            <span class="w-16 font-medium tabular-nums text-slate-900">{{ $c->numero }}</span><span class="text-slate-600">{{ $c->libelle }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-layout>
