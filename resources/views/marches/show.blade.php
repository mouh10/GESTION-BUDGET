<x-layout :titre="'Marché '.$marche->numero">
    <x-entete :titre="'Marché '.$marche->numero" :sous-titre="$marche->objet">
        @if (auth()->user()->estOrdonnateur())
            @if ($marche->statut === 'en_cours' && $marche->montant - $engage > 0)
                <a href="{{ route('engagements.create', ['marche_id' => $marche->id]) }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Engager sur ce marché</a>
            @endif
            <a href="{{ route('marches.edit', $marche) }}" class="btn-secondaire">Modifier</a>
        @endif
    </x-entete>
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="kpi"><p class="kpi-libelle">Montant du marché</p><p class="kpi-valeur text-2xl">{{ fcfa($marche->montant) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Engagé</p><p class="kpi-valeur text-2xl">{{ fcfa($engage) }}</p><div class="mt-2"><x-barre :taux="$marche->montant > 0 ? round($engage / $marche->montant * 100, 1) : null" /></div></div>
        <div class="kpi"><p class="kpi-libelle">Payé</p><p class="kpi-valeur text-2xl">{{ fcfa($paye) }}</p></div>
        <div class="kpi text-sm"><p class="kpi-libelle">Titulaire</p><p class="mt-2 font-medium"><a href="{{ route('tiers.show', $marche->tiers) }}" class="lien">{{ $marche->tiers->nom }}</a></p><p class="text-slate-500">{{ \App\Models\Marche::TYPES[$marche->type] }} · {{ \App\Models\Marche::MODES[$marche->mode_passation] }}</p></div>
    </div>
    <div class="carte overflow-x-auto">
        <div class="carte-entete"><h2>Engagements rattachés</h2>@if ($marche->ligneCredit)<span class="text-sm text-slate-500">Imputation : {{ $marche->ligneCredit->imputation() }}</span>@endif</div>
        <table class="tableau">
            <thead><tr><th>N°</th><th>Date</th><th>Objet</th><th class="num">Montant</th><th class="num">Payé</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($marche->engagements as $e)
                    <tr><td><a href="{{ route('engagements.show', $e) }}" class="lien font-medium">{{ $e->numero }}</a></td><td>{{ date_fr($e->date) }}</td><td>{{ $e->objet }}</td><td class="num">{{ montant($e->montant) }}</td><td class="num">{{ montant($e->montantPaye()) }}</td><td><x-statut :statut="$e->statut" /></td></tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucun engagement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
