@php
    $type = request('type') === 'redevable' ? 'redevable' : 'fournisseur';
    $titre = $type === 'redevable' ? 'Redevables' : 'Fournisseurs et bénéficiaires';
@endphp
<x-layout :titre="$titre">
    <x-entete :titre="$titre" :sous-titre="$type === 'redevable' ? 'Personnes et organismes débiteurs de recettes.' : 'Créanciers de l’administration : fournisseurs, prestataires, organismes, agents.'">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('tiers.create', ['type' => $type]) }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouveau {{ $type === 'redevable' ? 'redevable' : 'fournisseur' }}</a>
        @endif
    </x-entete>
    <form method="GET" class="mb-5 flex max-w-xl gap-2"><input type="hidden" name="type" value="{{ $type }}"><input name="q" value="{{ request('q') }}" class="champ" placeholder="Nom, code, téléphone"><button class="btn-secondaire">Rechercher</button></form>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Code</th><th>Nom</th><th>NINEA</th><th>Téléphone</th><th>Compte</th><th class="num">{{ $type === 'redevable' ? 'Reste à recouvrer' : 'Mandats non payés' }}</th></tr></thead>
            <tbody>
                @forelse ($tiers as $t)
                    <tr class="{{ $t->actif ? '' : 'opacity-60' }}">
                        <td class="tabular-nums">{{ $t->code }}</td>
                        <td><a href="{{ route('tiers.show', $t) }}" class="lien font-medium">{{ $t->nom }}</a></td>
                        <td>{{ $t->ninea }}</td><td>{{ $t->telephone }}</td>
                        <td class="tabular-nums">{{ $t->compte->numero }}</td>
                        <td class="num">{{ montant($t->resteARegler()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucun enregistrement.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $tiers->links() }}
    </div>
</x-layout>
