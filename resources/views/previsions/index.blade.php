<x-layout titre="Prévisions de recettes">
    <x-entete titre="Prévisions de recettes" :sous-titre="$exercice->libelle.' · prévu '.fcfa($total['prevu']).' · émis '.fcfa($total['emis']).' · recouvré '.fcfa($total['recouvre'])">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('titres.create') }}" class="btn-secondaire">Émettre un titre</a>
            <a href="{{ route('previsions.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouvelle prévision</a>
        @endif
    </x-entete>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Nature</th><th>Service</th><th class="num">Prévu</th><th class="num">Émis</th><th class="num">Recouvré</th><th class="num">Reste à recouvrer</th><th class="w-40">Recouvrement</th><th></th></tr></thead>
            <tbody>
                @forelse ($situation as $r)
                    <tr>
                        <td><span class="font-medium tabular-nums">{{ $r->prevision->nature->code }}</span> {{ $r->prevision->libelle ?: $r->prevision->nature->libelle }}<span class="block text-xs text-slate-500">{{ $r->prevision->nature->libelleTitre() }}</span></td>
                        <td>{{ $r->prevision->service?->code }}</td>
                        <td class="num">{{ montant($r->prevu) }}</td>
                        <td class="num">{{ montant($r->emis) }}</td>
                        <td class="num">{{ montant($r->recouvre) }}</td>
                        <td class="num">{{ montant($r->reste_a_recouvrer) }}</td>
                        <td><x-barre :taux="$r->taux_recouvrement" couleur="bg-emerald-500" /></td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('titres.index', ['q' => $r->prevision->nature->code]) }}" class="lien text-xs">Titres</a>
                            @if (auth()->user()->estOrdonnateur())
                                <a href="{{ route('titres.create', ['prevision_recette_id' => $r->prevision->id]) }}" class="lien ml-2 text-xs">+ Titre</a>
                                <a href="{{ route('previsions.edit', $r->prevision) }}" class="lien ml-2 text-xs">Modifier</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">Aucune prévision de recette.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><td colspan="2">Total</td><td class="num">{{ montant($total['prevu']) }}</td><td class="num">{{ montant($total['emis']) }}</td><td class="num">{{ montant($total['recouvre']) }}</td><td class="num">{{ montant($total['emis'] - $total['recouvre']) }}</td><td colspan="2"></td></tr></tfoot>
        </table>
    </div>
</x-layout>
