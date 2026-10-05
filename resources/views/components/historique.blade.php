@props(['entrees'])
<div class="carte">
    <div class="carte-entete"><h2>Historique</h2>@if (auth()->user()->estAdmin())<a href="{{ route('audit.index') }}" class="lien text-xs no-print">Journal d’audit</a>@endif</div>
    @if ($entrees->isEmpty())
        <p class="px-6 py-4 text-sm text-slate-500">Aucune action enregistrée.</p>
    @else
        <ol class="relative space-y-3 px-6 py-4 text-sm">
            @foreach ($entrees as $a)
                <li class="flex gap-3">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ match ($a->action) { 'statut' => 'bg-marque-500', 'suppression' => 'bg-red-500', 'piece_jointe' => 'bg-emerald-500', default => 'bg-slate-300' } }}"></span>
                    <div class="min-w-0">
                        <p class="text-slate-800"><span class="font-medium">{{ $a->description ?: $a->libelleAction() }}</span>@if ($a->sujet_type !== ($entrees->first()->sujet_type ?? null) || $entrees->pluck('sujet_libelle')->unique()->count() > 1)<span class="text-slate-500"> · {{ $a->sujet_libelle }}</span>@endif</p>
                        <p class="text-xs text-slate-500">{{ $a->created_at->format('d/m/Y à H:i') }} · {{ $a->user?->name ?? 'Système' }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
