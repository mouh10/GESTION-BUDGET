@props(['titre', 'sousTitre' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1>{{ $titre }}</h1>
        @if ($sousTitre)
            <p class="mt-1 text-sm text-slate-500">{{ $sousTitre }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="no-print flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
