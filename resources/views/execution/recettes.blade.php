<x-layout titre="Exécution des recettes">
    <x-entete titre="Situation d’exécution des recettes" :sous-titre="$exercice->libelle">
    </x-entete>
    @php $t = ['prevu' => $situation->sum('prevu'), 'emis' => $situation->sum('emis'), 'recouvre' => $situation->sum('recouvre')]; @endphp
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="kpi p-4"><p class="text-sm text-slate-500">Prévisions</p><p class="titre mt-1 text-2xl font-semibold">{{ fcfa($t['prevu']) }}</p></div>
        <div class="kpi p-4"><p class="text-sm text-slate-500">Émissions (titres)</p><p class="titre mt-1 text-2xl font-semibold">{{ fcfa($t['emis']) }}</p></div>
        <div class="kpi p-4"><p class="text-sm text-slate-500">Recouvrements</p><p class="titre mt-1 text-2xl font-semibold">{{ fcfa($t['recouvre']) }}</p></div>
    </div>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Nature</th><th class="num">Prévu</th><th class="num">Émis</th><th class="num">Recouvré</th><th class="num">Reste à recouvrer</th><th class="w-40">Taux de recouvrement</th></tr></thead>
            <tbody>
                @forelse ($parCategorie as $cat => $lignes)
                    <tr><td colspan="6" class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ \App\Models\Nature::CATEGORIES_RECETTE[$cat] ?? $cat }}</td></tr>
                    @foreach ($lignes as $r)
                        <tr>
                            <td><span class="font-medium tabular-nums">{{ $r->prevision->nature->code }}</span> {{ $r->prevision->libelle ?: $r->prevision->nature->libelle }}</td>
                            <td class="num">{{ montant($r->prevu) }}</td><td class="num">{{ montant($r->emis) }}</td><td class="num">{{ montant($r->recouvre) }}</td>
                            <td class="num">{{ montant($r->reste_a_recouvrer) }}</td><td><x-barre :taux="$r->taux_recouvrement" couleur="bg-emerald-500" /></td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucune prévision.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><td>Total</td><td class="num">{{ montant($t['prevu']) }}</td><td class="num">{{ montant($t['emis']) }}</td><td class="num">{{ montant($t['recouvre']) }}</td><td class="num">{{ montant($t['emis'] - $t['recouvre']) }}</td><td><x-barre :taux="$t['prevu'] > 0 ? round($t['recouvre'] / $t['prevu'] * 100, 1) : null" couleur="bg-emerald-500" /></td></tr></tfoot>
        </table>
    </div>
</x-layout>
