@props(['titre', 'sousTitre' => null, 'imprimer' => null])
@php
    // Bouton « Imprimer » affiché d'office sur les listes, les fiches et les états.
    $imprimable = $imprimer ?? request()->routeIs('*.index', '*.show', 'etats.*', 'execution.*', 'recherche');
@endphp
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div class="min-w-0">
        <h1>{{ $titre }}</h1>
        @if ($sousTitre)
            <p class="mt-1 text-sm text-slate-500">{{ $sousTitre }}</p>
        @endif
    </div>
    @if (trim($slot) || $imprimable)
        <div class="no-print flex flex-wrap items-center gap-2">
            @if ($imprimable)
                <a href="{{ request()->fullUrlWithQuery(['impression' => 1, 'page' => null]) }}" target="_blank" rel="noopener" class="btn-secondaire" title="Imprimer (la liste complète, sans pagination)">
                    <x-icone nom="imprimante" class="h-4 w-4" /> Imprimer
                </a>
            @endif
            {{ $slot }}
        </div>
    @endif
</div>
