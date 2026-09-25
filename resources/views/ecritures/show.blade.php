@php
    $source = $ecriture->source;
    $lienSource = match (true) {
        $source instanceof \App\Models\Mandat => [route('mandats.show', $source), 'Mandat '.$source->numero],
        $source instanceof \App\Models\TitreRecette => [route('titres.show', $source), 'Titre de recette '.$source->numero],
        $source instanceof \App\Models\MouvementTresorerie => [$source->mandat_id ? route('mandats.show', $source->mandat_id) : ($source->titre_recette_id ? route('titres.show', $source->titre_recette_id) : route('tresorerie.show', $source->compte_tresorerie_id)), $source->mandat_id ? 'Paiement de mandat' : ($source->titre_recette_id ? 'Recouvrement de titre' : 'Mouvement de trésorerie')],
        $source instanceof \App\Models\CompteTresorerie => [route('tresorerie.show', $source), 'Ouverture '.$source->nom],
        $source instanceof \App\Models\Exercice => [route('exercices.index'), 'Clôture '.$source->libelle],
        default => null,
    };
@endphp
<x-layout :titre="'Écriture '.$ecriture->numero_piece">
    <x-entete :titre="'Écriture '.$ecriture->numero_piece" :sous-titre="$ecriture->libelle">
        @if (auth()->user()->estComptable())
            @if (! $ecriture->estValidee() && ! $ecriture->estAutomatique())
                <a href="{{ route('ecritures.edit', $ecriture) }}" class="btn-secondaire">Modifier</a>
                <form method="POST" action="{{ route('ecritures.valider', $ecriture) }}" data-confirm="Valider cette écriture ? Elle ne sera plus modifiable.">
                    @csrf <button class="btn-primaire">Valider</button>
                </form>
                <form method="POST" action="{{ route('ecritures.destroy', $ecriture) }}" data-confirm="Supprimer ce brouillon ?">
                    @csrf @method('DELETE') <button class="btn-danger">Supprimer</button>
                </form>
            @elseif ($ecriture->estValidee() && ! $ecriture->estAutomatique() && ! $ecriture->exercice->cloture)
                <form method="POST" action="{{ route('ecritures.contre-passer', $ecriture) }}" data-confirm="Passer une écriture inverse pour annuler celle-ci ?">
                    @csrf <button class="btn-danger">Contre-passer</button>
                </form>
            @endif
        @endif
        <a href="{{ route('ecritures.index') }}" class="btn-secondaire">Retour</a>
    </x-entete>

    <div class="carte carte-corps mb-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
        <div><p class="text-slate-500">Date</p><p class="font-medium">{{ date_fr($ecriture->date) }}</p></div>
        <div><p class="text-slate-500">Journal</p><p class="font-medium">{{ $ecriture->journal->intitule }}</p></div>
        <div><p class="text-slate-500">Référence</p><p class="font-medium">{{ $ecriture->reference ?: '—' }}</p></div>
        <div><p class="text-slate-500">Statut</p><p><span class="{{ $ecriture->estValidee() ? 'badge-vert' : 'badge-gris' }}">{{ \App\Models\Ecriture::STATUTS[$ecriture->statut] }}</span></p></div>
        <div>
            <p class="text-slate-500">Origine</p>
            <p class="font-medium">
                @if ($lienSource)
                    <a href="{{ $lienSource[0] }}" class="lien">{{ $lienSource[1] }}</a>
                @else
                    Saisie manuelle{{ $ecriture->user ? ' · '.$ecriture->user->name : '' }}
                @endif
            </p>
        </div>
    </div>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Compte</th><th>Intitulé</th><th>Tiers</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th></tr></thead>
            <tbody>
                @foreach ($ecriture->lignes as $l)
                    <tr>
                        <td class="font-medium tabular-nums">{{ $l->compte->numero }}</td>
                        <td>{{ $l->compte->libelle }}</td>
                        <td>@if ($l->tiers)<a href="{{ route('tiers.show', $l->tiers) }}" class="lien">{{ $l->tiers->nom }}</a>@endif</td>
                        <td class="text-slate-600">{{ $l->libelle }}</td>
                        <td class="num">{{ $l->debit > 0 ? montant($l->debit) : '' }}</td>
                        <td class="num">{{ $l->credit > 0 ? montant($l->credit) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="4">Totaux</td><td class="num">{{ montant($ecriture->totalDebit()) }}</td><td class="num">{{ montant($ecriture->totalCredit()) }}</td></tr>
            </tfoot>
        </table>
    </div>
</x-layout>
