<x-layout titre="Services gestionnaires">
    <x-entete titre="Services gestionnaires" sous-titre="Classification administrative : directions et services qui gèrent des crédits.">
        @if (auth()->user()->estAdmin())
            <a href="{{ route('services.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouveau service</a>
        @endif
    </x-entete>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Code</th><th>Libellé</th><th>Responsable</th><th class="num">Lignes de crédits</th><th></th></tr></thead>
            <tbody>
                @forelse ($services as $s)
                    <tr class="{{ $s->actif ? '' : 'opacity-60' }}">
                        <td class="font-semibold">{{ $s->code }}</td>
                        <td>{{ $s->libelle }}</td>
                        <td>{{ $s->responsable }}</td>
                        <td class="num">{{ $s->lignes_credit_count }}</td>
                        <td class="text-right">
                            <a href="{{ route('credits.index', ['service_id' => $s->id]) }}" class="lien text-xs">Crédits</a>
                            @if (auth()->user()->estAdmin())<a href="{{ route('services.edit', $s) }}" class="lien ml-3 text-xs">Modifier</a>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-slate-500">Aucun service.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
