<x-layout titre="Plan comptable">
    <x-entete titre="Plan comptable SYSCOHADA" sous-titre="Les comptes à 2 chiffres servent de rubriques ; la saisie se fait sur les comptes de 3 chiffres et plus.">
        @if (auth()->user()->estComptable())
            <a href="{{ route('comptes.create') }}" class="btn-primaire">Nouveau compte</a>
        @endif
    </x-entete>

    <form method="GET" class="carte carte-corps mb-4 flex flex-wrap items-end gap-3">
        <div class="w-56">
            <label class="etiquette" for="q">Recherche</label>
            <input id="q" name="q" value="{{ request('q') }}" class="champ" placeholder="Numéro ou libellé">
        </div>
        <div class="w-72">
            <label class="etiquette" for="classe">Classe</label>
            <select id="classe" name="classe" class="champ" data-auto-submit>
                <option value="">Toutes les classes</option>
                @foreach (\App\Models\Compte::CLASSES as $n => $lib)
                    <option value="{{ $n }}" @selected(request('classe') == $n)>{{ $n }} - {{ $lib }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-secondaire">Filtrer</button>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Numéro</th><th>Libellé</th><th>Classe</th><th>État</th><th></th></tr></thead>
            <tbody>
                @forelse ($comptes as $c)
                    @php $rubrique = strlen($c->numero) <= 2; @endphp
                    <tr class="{{ $rubrique ? 'bg-slate-50 font-semibold' : '' }}">
                        <td class="tabular-nums {{ $rubrique ? '' : 'pl-8' }}">{{ $c->numero }}</td>
                        <td>{{ $c->libelle }}</td>
                        <td>{{ $c->classe }}</td>
                        <td>@unless ($c->actif)<span class="badge-gris">Inactif</span>@endunless</td>
                        <td class="text-right whitespace-nowrap">
                            @if (auth()->user()->estComptable())
                                <a href="{{ route('comptes.create', ['parent' => $c->numero]) }}" class="lien text-xs">+ sous-compte</a>
                                <a href="{{ route('comptes.edit', $c) }}" class="lien ml-3 text-xs">Modifier</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-slate-500">Aucun compte trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $comptes->links() }}
    </div>
</x-layout>
