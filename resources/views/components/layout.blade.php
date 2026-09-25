@props(['titre' => null])
@php
    $exerciceCourant = \App\Models\Exercice::courant();
    $exercicesListe = \App\Models\Exercice::orderByDesc('date_debut')->get();
    $utilisateur = auth()->user();
    $actif = fn (string ...$motifs) => request()->routeIs(...$motifs) ? 'actif' : '';
    $initiales = collect(preg_split('/\s+/', trim($utilisateur->name)))->filter()->take(2)
        ->map(fn ($m) => mb_strtoupper(mb_substr($m, 0, 1)))->join('');

    // Notifications : ce qui attend une action de l'utilisateur selon son rôle.
    $notifications = collect();
    if ($exerciceCourant) {
        $ex = $exerciceCourant->id;
        $mandats = fn () => \App\Models\Mandat::whereHas('liquidation.engagement', fn ($q) => $q->where('exercice_id', $ex));
        $ajout = function (bool $pourMoi, int $n, string $titre, string $detail, string $url, string $icone, string $couleur) use (&$notifications) {
            if ($pourMoi && $n > 0) {
                $notifications->push(compact('n', 'titre', 'detail', 'url', 'icone', 'couleur'));
            }
        };
        $ajout($utilisateur->estControleur(), \App\Models\Engagement::where('exercice_id', $ex)->where('statut', 'soumis')->count(),
            'engagement(s) à viser', 'En attente du contrôle financier', route('engagements.index', ['statut' => 'soumis']), 'bouclier', 'bg-amber-50 text-amber-600');
        $ajout($utilisateur->estOrdonnateur(), \App\Models\Engagement::where('exercice_id', $ex)->where('statut', 'rejete')->count(),
            'engagement(s) rejeté(s)', 'À corriger et soumettre à nouveau', route('engagements.index', ['statut' => 'rejete']), 'alerte', 'bg-red-50 text-red-600');
        $ajout($utilisateur->estOrdonnateur(), \App\Models\Mandat::where('statut', 'rejete')->whereHas('liquidation', fn ($q) => $q->where('statut', 'validee'))->whereDoesntHave('liquidation.mandats', fn ($q) => $q->where('statut', '!=', 'rejete'))->count(),
            'mandat(s) rejeté(s) par le comptable', 'À réémettre ou à annuler', route('mandats.index', ['statut' => 'rejete']), 'alerte', 'bg-red-50 text-red-600');
        $ajout($utilisateur->estComptable(), $mandats()->where('statut', 'emis')->count(),
            'mandat(s) à prendre en charge', 'Transmis par l’ordonnateur', route('mandats.index', ['statut' => 'emis']), 'recu', 'bg-marque-50 text-marque-600');
        $ajout($utilisateur->estComptable(), $mandats()->where('statut', 'pris_en_charge')->count(),
            'mandat(s) à payer', 'Pris en charge, en attente de paiement', route('mandats.index', ['statut' => 'pris_en_charge']), 'portefeuille', 'bg-emerald-50 text-emerald-600');
        $ajout($utilisateur->estAdmin(), \App\Models\Modification::where('exercice_id', $ex)->where('statut', 'brouillon')->count(),
            'acte(s) de modification à approuver', 'Virements, transferts, gels…', route('modifications.index'), 'virement', 'bg-violet-50 text-violet-600');
        $ajout($utilisateur->estComptable(), \App\Models\Ecriture::where('exercice_id', $ex)->where('statut', 'brouillon')->count(),
            'écriture(s) en brouillon', 'À valider pour apparaître dans les états', route('ecritures.index', ['statut' => 'brouillon']), 'stylo', 'bg-slate-100 text-slate-600');
    }
    $nbNotifications = $notifications->sum('n');

    $menu = [
        'Principal' => [
            ['Tableau de bord', route('dashboard'), 'tableau', $actif('dashboard')],
        ],
        'Budget' => [
            ['Crédits (AE / CP)', route('credits.index'), 'cible', $actif('credits.*')],
            ['Prévisions de recettes', route('previsions.index'), 'hausse', $actif('previsions.*')],
            ['Modifications budgétaires', route('modifications.index'), 'virement', $actif('modifications.*')],
        ],
        'Dépenses' => [
            ['Engagements', route('engagements.index'), 'stylo', $actif('engagements.*')],
            ['Mandats et paiements', route('mandats.index'), 'recu', $actif('mandats.*')],
            ['Marchés et contrats', route('marches.index'), 'carnet', $actif('marches.*')],
            ['Fournisseurs', route('tiers.index', ['type' => 'fournisseur']), 'camion', request()->routeIs('tiers.*') && request('type') !== 'redevable' && ! (request()->route('tiers')?->estRedevable()) ? 'actif' : ''],
        ],
        'Recettes' => [
            ['Titres de recette', route('titres.index'), 'facture', $actif('titres.*')],
            ['Redevables', route('tiers.index', ['type' => 'redevable']), 'clients', request()->routeIs('tiers.*') && (request('type') === 'redevable' || request()->route('tiers')?->estRedevable()) ? 'actif' : ''],
        ],
        'Trésorerie' => array_filter([
            ['Comptes de trésorerie', route('tresorerie.index'), 'banque', request()->routeIs('tresorerie.*') && ! request()->routeIs('tresorerie.virement') ? 'actif' : ''],
            $utilisateur->estComptable() ? ['Virement interne', route('tresorerie.virement'), 'portefeuille', $actif('tresorerie.virement')] : null,
        ]),
        'Exécution' => [
            ['Exécution des dépenses', route('execution.depenses'), 'camembert', $actif('execution.depenses')],
            ['Exécution des recettes', route('execution.recettes'), 'balance', $actif('execution.recettes')],
        ],
        'Comptabilité' => [
            ['Écritures', route('ecritures.index'), 'stylo', $actif('ecritures.*')],
            ['Livre journal', route('etats.journal'), 'livre-ouvert', $actif('etats.journal')],
            ['Grand livre', route('etats.grand-livre'), 'livre', $actif('etats.grand-livre')],
            ['Balance', route('etats.balance'), 'balance', $actif('etats.balance')],
            ['Bilan', route('etats.bilan'), 'camembert', $actif('etats.bilan')],
            ['Compte de résultat', route('etats.resultat'), 'hausse', $actif('etats.resultat')],
        ],
        'Paramètres' => array_filter([
            ['Programmes et actions', route('programmes.index'), 'arbre', $actif('programmes.*')],
            ['Services gestionnaires', route('services.index'), 'clients', $actif('services.*')],
            ['Nomenclature économique', route('natures.index'), 'carnet', $actif('natures.*')],
            ['Plan comptable', route('comptes.index'), 'arbre', $actif('comptes.*')],
            ['Journaux', route('journaux.index'), 'carnet', $actif('journaux.*')],
            ['Exercices', route('exercices.index'), 'calendrier', $actif('exercices.*')],
            $utilisateur->estAdmin() ? ['Utilisateurs', route('utilisateurs.index'), 'bouclier', $actif('utilisateurs.*')] : null,
        ]),
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titre ? $titre.' · ' : '' }}{{ config('app.name', 'Gestion') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div id="menu-fond" class="no-print fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden" data-menu-toggle></div>

{{-- Menu latéral --}}
<aside id="menu-lateral" class="no-print fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-marque-500 text-white shadow-sm">
            <x-icone nom="calculatrice" class="h-6 w-6" />
        </span>
        <span class="leading-tight">
            <span class="titre block text-xl font-bold uppercase tracking-tight text-slate-900">{{ config('app.name', 'Gestion') }}</span>
            <span class="block text-sm text-slate-500">{{ config('gestion.entreprise.nom') }}</span>
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto px-4 pb-6">
        @foreach ($menu as $section => $liens)
            <p class="nav-titre">{{ $section }}</p>
            <div class="space-y-1">
                @foreach ($liens as [$libelle, $url, $icone, $classe])
                    <a href="{{ $url }}" class="nav-lien {{ $classe }}">
                        <x-icone :nom="$icone" />
                        <span>{{ $libelle }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>

<div class="lg:pl-72">
    {{-- Barre supérieure --}}
    <header class="no-print sticky top-0 z-20 flex h-[76px] items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
        <button type="button" class="btn-secondaire btn-petit lg:hidden" data-menu-toggle aria-label="Ouvrir le menu">
            <x-icone nom="menu" class="h-5 w-5" />
        </button>

        <form method="GET" action="{{ route('recherche') }}" class="relative hidden w-full max-w-xl md:block">
            <x-icone nom="recherche" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input type="search" name="q" value="{{ request()->routeIs('recherche') ? request('q') : '' }}" data-recherche
                   class="champ bg-slate-50 pl-11" placeholder="Rechercher un engagement, un mandat, un fournisseur… (Ctrl+K)" autocomplete="off">
        </form>

        <div class="ml-auto flex items-center gap-2 sm:gap-4">
            @if ($exercicesListe->isNotEmpty())
                <form method="POST" action="{{ route('exercices.selectionner') }}">
                    @csrf
                    <label for="exercice_id" class="sr-only">Exercice</label>
                    <select id="exercice_id" name="exercice_id" class="champ-petit w-auto bg-slate-50" data-auto-submit title="Exercice comptable">
                        @foreach ($exercicesListe as $ex)
                            <option value="{{ $ex->id }}" @selected($exerciceCourant?->id === $ex->id)>{{ $ex->libelle }}{{ $ex->cloture ? ' (clôturé)' : '' }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            {{-- Notifications --}}
            <details class="relative" data-deroulant>
                <summary class="relative flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-slate-600 hover:bg-slate-100" aria-label="Notifications">
                    <x-icone nom="cloche" class="h-6 w-6" />
                    @if ($nbNotifications > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white ring-2 ring-white">{{ $nbNotifications > 99 ? '99+' : $nbNotifications }}</span>
                    @endif
                </summary>
                <div class="menu-deroulant">
                    <p class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">Notifications</p>
                    <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto text-sm">
                        @forelse ($notifications as $n)
                            <li>
                                <a href="{{ $n['url'] }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $n['couleur'] }}"><x-icone :nom="$n['icone']" class="h-4 w-4" /></span>
                                    <span>
                                        <span class="block font-medium text-slate-900">{{ $n['n'] }} {{ $n['titre'] }}</span>
                                        <span class="block text-xs text-slate-500">{{ $n['detail'] }}</span>
                                    </span>
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-slate-500">Rien à traiter pour le moment.</li>
                        @endforelse
                    </ul>
                </div>
            </details>

            {{-- Profil --}}
            <details class="relative" data-deroulant>
                <summary class="flex cursor-pointer items-center gap-3 rounded-full py-1 pl-1 pr-2 hover:bg-slate-100">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-nuit text-sm font-semibold text-white">{{ $initiales }}</span>
                    <span class="hidden text-[15px] font-medium text-slate-900 sm:block">{{ $utilisateur->name }}</span>
                    <x-icone nom="chevron" class="hidden h-4 w-4 text-slate-400 sm:block" />
                </summary>
                <div class="menu-deroulant w-64">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="font-medium text-slate-900">{{ $utilisateur->name }}</p>
                        <p class="text-xs text-slate-500">{{ $utilisateur->email }}</p>
                        <span class="badge-bleu mt-2">{{ $utilisateur->libelleRole() }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-red-700 hover:bg-red-50">
                            <x-icone nom="sortie" class="h-4 w-4" /> Déconnexion
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </header>

    <main class="mx-auto max-w-[1400px] px-4 py-8 sm:px-8">
        @if (session('succes'))
            <div class="no-print mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('succes') }}</div>
        @endif
        @if (session('erreur'))
            <div class="no-print mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('erreur') }}</div>
        @endif
        @if ($errors->any())
            <div class="no-print mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium">Veuillez corriger les erreurs suivantes :</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>
</div>
</body>
</html>
