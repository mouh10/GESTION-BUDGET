@props(['titre' => null])
@php
    [$moduleCle, $moduleLibelle] = module_actif();
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
        // Deux requêtes groupées au lieu d'une par compteur.
        $engParStatut = \App\Models\Engagement::where('exercice_id', $ex)->whereIn('statut', ['soumis', 'rejete'])
            ->groupBy('statut')->selectRaw('statut, COUNT(*) as n')->pluck('n', 'statut');
        $mdParStatut = $mandats()->whereIn('statut', ['emis', 'pris_en_charge'])
            ->groupBy('statut')->selectRaw('statut, COUNT(*) as n')->pluck('n', 'statut');

        $ajout($utilisateur->estControleur(), (int) ($engParStatut['soumis'] ?? 0),
            'engagement(s) à viser', 'En attente du contrôle financier', route('engagements.index', ['statut' => 'soumis']), 'bouclier', 'bg-amber-50 text-amber-600');
        $ajout($utilisateur->estOrdonnateur(), (int) ($engParStatut['rejete'] ?? 0),
            'engagement(s) rejeté(s)', 'À corriger et soumettre à nouveau', route('engagements.index', ['statut' => 'rejete']), 'alerte', 'bg-red-50 text-red-600');
        if ($utilisateur->estOrdonnateur()) {
            $ajout(true, \App\Models\Mandat::where('statut', 'rejete')->whereHas('liquidation', fn ($q) => $q->where('statut', 'validee'))->whereDoesntHave('liquidation.mandats', fn ($q) => $q->where('statut', '!=', 'rejete'))->count(),
                'mandat(s) rejeté(s) par le comptable', 'À réémettre ou à annuler', route('mandats.index', ['statut' => 'rejete']), 'alerte', 'bg-red-50 text-red-600');
        }
        $ajout($utilisateur->estComptable(), (int) ($mdParStatut['emis'] ?? 0),
            'mandat(s) à prendre en charge', 'Transmis par l’ordonnateur', route('mandats.index', ['statut' => 'emis']), 'recu', 'bg-marque-50 text-marque-600');
        $ajout($utilisateur->estComptable(), (int) ($mdParStatut['pris_en_charge'] ?? 0),
            'mandat(s) à payer', 'Pris en charge, en attente de paiement', route('mandats.index', ['statut' => 'pris_en_charge']), 'portefeuille', 'bg-emerald-50 text-emerald-600');
        if ($utilisateur->estAdmin()) {
            $ajout(true, \App\Models\Modification::where('exercice_id', $ex)->where('statut', 'brouillon')->count(),
                'acte(s) de modification à approuver', 'Virements, transferts, gels…', route('modifications.index'), 'virement', 'bg-violet-50 text-violet-600');
        }
        if ($utilisateur->estComptable()) {
            $ajout(true, \App\Models\Ecriture::where('exercice_id', $ex)->where('statut', 'brouillon')->count(),
                'écriture(s) en brouillon', 'À valider pour apparaître dans les états', route('ecritures.index', ['statut' => 'brouillon']), 'stylo', 'bg-slate-100 text-slate-600');
        }
    }
    $nbNotifications = $notifications->sum('n');

    // Menu principal : peu d'entrées, regroupées ; les sous-pages sont des onglets en haut de page.
    $menu = [
        [
            ['Tableau de bord', route('dashboard'), 'tableau', request()->routeIs('dashboard', 'recherche')],
        ],
        [
            ['Budget', route('credits.index'), 'cible', $moduleCle === 'budget'],
            ['Engagements', route('engagements.index'), 'stylo', request()->routeIs('engagements.*', 'marches.*') || ($moduleCle === 'depenses' && request()->routeIs('tiers.*'))],
            ['Mandats', route('mandats.index'), 'recu', request()->routeIs('mandats.*')],
            ['Recettes', route('titres.index'), 'facture', $moduleCle === 'recettes'],
            ['Trésorerie', route('tresorerie.index'), 'banque', $moduleCle === 'tresorerie'],
        ],
        [
            ['États d’exécution', route('execution.depenses'), 'camembert', $moduleCle === 'execution'],
            ['Comptabilité', route('ecritures.index'), 'livre', $moduleCle === 'comptabilite'],
        ],
        [
            ['Paramètres', route('programmes.index'), 'parametres', $moduleCle === 'parametres'],
        ],
    ];

    // Onglets de chaque rubrique.
    $onglets = match ($moduleCle) {
        'budget' => [['Crédits (AE / CP)', 'credits.index', [], 'credits.*'], ['Prévisions de recettes', 'previsions.index', [], 'previsions.*'], ['Modifications budgétaires', 'modifications.index', [], 'modifications.*']],
        'depenses' => request()->routeIs('mandats.*') ? [] : [['Engagements', 'engagements.index', [], 'engagements.*'], ['Marchés et contrats', 'marches.index', [], 'marches.*'], ['Fournisseurs', 'tiers.index', ['type' => 'fournisseur'], 'tiers.*']],
        'recettes' => [['Titres de recette', 'titres.index', [], 'titres.*'], ['Redevables', 'tiers.index', ['type' => 'redevable'], 'tiers.*']],
        'tresorerie' => array_values(array_filter([['Comptes de trésorerie', 'tresorerie.index', [], 'tresorerie.*'], $utilisateur->estComptable() ? ['Virement interne', 'tresorerie.virement', [], 'tresorerie.virement'] : null])),
        'execution' => [['Dépenses', 'execution.depenses', [], 'execution.depenses'], ['Recettes', 'execution.recettes', [], 'execution.recettes']],
        'comptabilite' => [['Écritures', 'ecritures.index', [], 'ecritures.*'], ['Livre journal', 'etats.journal', [], 'etats.journal'], ['Grand livre', 'etats.grand-livre', [], 'etats.grand-livre'], ['Balance', 'etats.balance', [], 'etats.balance'], ['Bilan', 'etats.bilan', [], 'etats.bilan'], ['Compte de résultat', 'etats.resultat', [], 'etats.resultat']],
        'parametres' => array_values(array_filter([['Programmes', 'programmes.index', [], 'programmes.*'], ['Services', 'services.index', [], 'services.*'], ['Nomenclature', 'natures.index', [], 'natures.*'], ['Plan comptable', 'comptes.index', [], 'comptes.*'], ['Journaux', 'journaux.index', [], 'journaux.*'], ['Exercices', 'exercices.index', [], 'exercices.*'], $utilisateur->estAdmin() ? ['Utilisateurs', 'utilisateurs.index', [], 'utilisateurs.*'] : null, $utilisateur->estAdmin() ? ['Journal d’audit', 'audit.index', [], 'audit.*'] : null, $utilisateur->estAdmin() ? ['Sauvegardes', 'sauvegardes.index', [], 'sauvegardes.*'] : null])),
        default => [],
    };
    // Sur une page de rubrique, un seul onglet est actif (le plus précis).
    $ongletActif = null;
    foreach ($onglets as $i => $o) {
        if (request()->routeIs($o[3])) {
            $ongletActif = $i;
            if (request()->routeIs($o[1])) {
                break;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titre ? $titre.' · ' : '' }}{{ config('app.name', 'Gestion') }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%234a90e2'/%3E%3Ctext x='16' y='22' font-family='Arial' font-weight='700' font-size='17' fill='white' text-anchor='middle'%3EG%3C/text%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" @if (en_impression()) data-impression @endif>
<div id="menu-fond" class="no-print fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden" data-menu-toggle></div>

{{-- Menu latéral --}}
<aside id="menu-lateral" class="no-print fixed inset-y-0 left-0 z-40 flex w-60 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
    <div class="flex items-center justify-between gap-2 px-5 py-5">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-marque-600 text-white"><x-icone nom="calculatrice" class="h-4 w-4" /></span>
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-[15px] font-semibold text-slate-900">{{ config('app.name', 'Gestion') }}</span>
                <span class="block truncate text-xs text-slate-500">{{ config('gestion.entreprise.nom') }}</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 pt-4 pb-6" data-menu-defilement>
        @foreach ($menu as $groupe)
            <div class="space-y-0.5">
                @foreach ($groupe as [$libelle, $url, $icone, $estActif])
                    <a href="{{ $url }}" class="nav-lien {{ $estActif ? 'actif' : '' }}">
                        <x-icone :nom="$icone" />
                        <span>{{ $libelle }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400">{{ $exerciceCourant?->libelle }} · {{ $utilisateur->libelleRole() }}</div>
</aside>

<div class="lg:pl-60">
    {{-- Barre supérieure --}}
    <header class="no-print sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
        <button type="button" class="btn-secondaire btn-petit lg:hidden" data-menu-toggle aria-label="Ouvrir le menu">
            <x-icone nom="menu" class="h-5 w-5" />
        </button>

        <form method="GET" action="{{ route('recherche') }}" class="relative hidden w-full max-w-xl md:block">
            <x-icone nom="recherche" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input type="search" name="q" value="{{ request()->routeIs('recherche') ? request('q') : '' }}" data-recherche
                   class="champ bg-slate-50 pl-11" placeholder="Rechercher un engagement, un mandat, un fournisseur… (Ctrl+K)" autocomplete="off">
        </form>

        <div class="ml-auto flex items-center gap-2 sm:gap-4">
            @if ($utilisateur->estRestreint())
                <span class="hidden rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 lg:inline" title="Vous ne voyez que les crédits, dépenses et recettes de votre service">Périmètre : {{ $utilisateur->service?->code }}</span>
            @endif
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
                    <span class="hidden text-sm font-medium text-slate-900 sm:block">{{ $utilisateur->name }}</span>
                    <x-icone nom="chevron" class="hidden h-4 w-4 text-slate-400 sm:block" />
                </summary>
                <div class="menu-deroulant w-64">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="font-medium text-slate-900">{{ $utilisateur->name }}</p>
                        <p class="text-xs text-slate-500">{{ $utilisateur->email }}</p>
                        <span class="badge-bleu mt-2">{{ $utilisateur->libelleRole() }}</span>
                        @if ($utilisateur->estRestreint())<span class="badge-gris mt-2">Service {{ $utilisateur->service?->code }}</span>@endif
                    </div>
                    <a href="{{ route('mot-de-passe.edit') }}" class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <x-icone nom="bouclier" class="h-4 w-4" /> Changer mon mot de passe
                    </a>
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

    <main class="mx-auto max-w-[1400px] px-4 py-7 sm:px-10">
        {{-- En-tête officiel, visible seulement à l'impression --}}
        <div class="entete-impression">
            <div>
                @if (config('gestion.entreprise.tutelle'))<p class="text-xs font-bold uppercase tracking-wider">{{ config('gestion.entreprise.tutelle') }}</p>@endif
                @if (config('gestion.entreprise.devise_nationale'))<p class="text-[10px] italic">{{ config('gestion.entreprise.devise_nationale') }}</p>@endif
                <p class="mt-1 font-bold uppercase">{{ config('gestion.entreprise.nom') }}</p>
                @if (config('gestion.entreprise.adresse'))<p class="text-xs">{{ config('gestion.entreprise.adresse') }}</p>@endif
            </div>
            <div class="text-right text-xs">
                <p>{{ $exerciceCourant?->libelle }}</p>
                <p>Édité le {{ now()->format('d/m/Y à H:i') }}</p>
                <p>par {{ $utilisateur->name }}</p>
            </div>
        </div>
        @if ($onglets)
            <nav class="onglets no-print mb-6" aria-label="Sous-rubriques">
                @foreach ($onglets as $i => [$libelle, $route, $params])
                    <a href="{{ route($route, $params) }}" class="onglet {{ $ongletActif === $i ? 'actif' : '' }}">{{ $libelle }}</a>
                @endforeach
            </nav>
        @endif
        @if (session('succes'))
            <div class="alerte-flash no-print mb-5 flex items-start justify-between gap-3 rounded-xl border border-emerald-200 border-l-4 border-l-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" data-flash>{{ session('succes') }}<button type="button" class="text-emerald-700/60 hover:text-emerald-900" data-fermer aria-label="Fermer">✕</button></div>
        @endif
        @if (session('erreur'))
            <div class="no-print mb-5 flex items-start justify-between gap-3 rounded-xl border border-red-200 border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('erreur') }}<button type="button" class="text-red-700/60 hover:text-red-900" data-fermer aria-label="Fermer">✕</button></div>
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

        <p class="pied-impression">{{ config('app.name', 'Gestion') }} · {{ config('gestion.entreprise.nom') }} · document édité le {{ now()->format('d/m/Y à H:i') }}</p>
    </main>
</div>
</body>
</html>
