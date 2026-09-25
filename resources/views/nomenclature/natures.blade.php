@php $liste = $type === 'recette' ? \App\Models\Nature::CATEGORIES_RECETTE : \App\Models\Nature::TITRES_DEPENSE; @endphp
<x-layout titre="Nomenclature économique">
    <x-entete titre="Nomenclature économique" sous-titre="Natures de dépenses (par titre) et de recettes (par catégorie), avec leur imputation comptable.">
        @if (auth()->user()->estAdmin())
            <a href="{{ route('natures.create', ['type' => $type]) }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouvelle nature</a>
        @endif
    </x-entete>

    <div class="mb-5 inline-flex rounded-xl border border-slate-200 bg-white p-1 text-sm">
        <a href="{{ route('natures.index', ['type' => 'depense']) }}" class="rounded-lg px-4 py-1.5 {{ $type === 'depense' ? 'bg-nuit text-white' : 'text-slate-600' }}">Dépenses</a>
        <a href="{{ route('natures.index', ['type' => 'recette']) }}" class="rounded-lg px-4 py-1.5 {{ $type === 'recette' ? 'bg-nuit text-white' : 'text-slate-600' }}">Recettes</a>
    </div>

    <div class="space-y-5">
        @forelse ($natures as $titre => $groupe)
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2 class="text-base">{{ $type === 'recette' ? 'Catégorie' : 'Titre' }} {{ $titre }} — {{ $liste[$titre] ?? '' }}</h2></div>
                <table class="tableau">
                    <thead><tr><th class="w-24">Code</th><th>Libellé</th><th>Imputation comptable</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($groupe as $n)
                            <tr class="{{ $n->actif ? '' : 'opacity-60' }}">
                                <td class="font-semibold tabular-nums">{{ $n->code }}</td>
                                <td>{{ $n->libelle }}</td>
                                <td class="text-slate-600">{!! $n->compte ? e($n->compte->numero.' - '.$n->compte->libelle) : '<span class="badge-rouge">À renseigner</span>' !!}</td>
                                <td class="text-right">@if (auth()->user()->estAdmin())<a href="{{ route('natures.edit', $n) }}" class="lien text-xs">Modifier</a>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="carte carte-corps text-slate-500">Aucune nature.</div>
        @endforelse
    </div>
</x-layout>
