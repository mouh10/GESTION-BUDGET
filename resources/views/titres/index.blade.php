<x-layout titre="Titres de recette">
    <x-entete titre="Titres de recette" :sous-titre="'Émis : '.fcfa($totaux->emis).' · recouvré : '.fcfa($totaux->recouvre).' · reste : '.fcfa($totaux->emis - $totaux->recouvre)">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('titres.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Émettre un titre</a>
        @endif
    </x-entete>
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('titres.index') }}" class="{{ request('statut') ? 'btn-secondaire' : 'btn-primaire' }} btn-petit">Tous</a>
        @foreach (\App\Models\TitreRecette::STATUTS as $v => $l)<a href="{{ route('titres.index', ['statut' => $v]) }}" class="{{ request('statut') === $v ? 'btn-primaire' : 'btn-secondaire' }} btn-petit">{{ $l }}</a>@endforeach
    </div>
    <form method="GET" class="mb-5 flex max-w-xl gap-2"><input type="hidden" name="statut" value="{{ request('statut') }}"><input name="q" value="{{ request('q') }}" class="champ" placeholder="N°, objet, redevable"><button class="btn-secondaire">Rechercher</button></form>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>N°</th><th>Date</th><th>Redevable</th><th>Objet</th><th>Nature</th><th class="num">Montant</th><th class="num">Recouvré</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($titres as $t)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('titres.show', $t) }}" class="lien font-medium">{{ $t->numero }}</a></td>
                        <td class="whitespace-nowrap">{{ date_fr($t->date) }}</td>
                        <td>{{ $t->tiers->nom }}</td><td>{{ $t->objet }}</td>
                        <td class="text-xs">{{ $t->prevision->nature->code }} {{ $t->prevision->nature->libelle }}</td>
                        <td class="num">{{ montant($t->montant) }}</td><td class="num">{{ montant($t->montant_recouvre) }}</td>
                        <td><x-statut :statut="$t->statut" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">Aucun titre.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $titres->links() }}
    </div>
</x-layout>
