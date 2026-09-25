@props(['taux' => null, 'couleur' => null])
@php
    $t = $taux ?? 0;
    $c = $couleur ?? ($t > 100 ? 'bg-red-500' : ($t >= config('gestion.alerte_budget') ? 'bg-amber-500' : 'bg-marque-500'));
@endphp
<div class="flex items-center gap-2">
    <div class="h-2 min-w-16 flex-1 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full {{ $c }}" style="width: {{ min(100, max(0, $t)) }}%"></div>
    </div>
    <span class="w-12 text-right text-xs tabular-nums text-slate-600">{{ $taux !== null ? montant($taux, 1).' %' : '—' }}</span>
</div>
