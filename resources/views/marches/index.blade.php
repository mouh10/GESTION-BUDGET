<x-layout titre="Marchés et contrats">
    <x-entete titre="Marchés et contrats" sous-titre="Marchés publics attribués ; les engagements rattachés ne peuvent pas dépasser leur montant.">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('marches.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouveau marché</a>
        @endif
    </x-entete>
    <form method="GET" class="mb-5 flex max-w-xl gap-2"><input name="q" value="{{ request('q') }}" class="champ" placeholder="N° ou objet du marché"><button class="btn-secondaire">Rechercher</button></form>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>N°</th><th>Objet</th><th>Titulaire</th><th>Type / mode</th><th class="num">Montant</th><th class="num">Engagé</th><th class="w-36">Consommation</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($marches as $m)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('marches.show', $m) }}" class="lien font-medium">{{ $m->numero }}</a><span class="block text-xs text-slate-500">{{ date_fr($m->date_signature) }}</span></td>
                        <td>{{ $m->objet }}</td>
                        <td>{{ $m->tiers->nom }}</td>
                        <td class="text-xs">{{ \App\Models\Marche::TYPES[$m->type] }}<span class="block text-slate-500">{{ \App\Models\Marche::MODES[$m->mode_passation] }}</span></td>
                        <td class="num">{{ montant($m->montant) }}</td>
                        <td class="num">{{ montant($m->engage) }}</td>
                        <td><x-barre :taux="$m->montant > 0 ? round($m->engage / $m->montant * 100, 1) : null" /></td>
                        <td><x-statut :statut="$m->statut" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">Aucun marché.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
