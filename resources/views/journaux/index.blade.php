<x-layout titre="Journaux">
    <x-entete titre="Journaux comptables">
        @if (auth()->user()->estComptable())
            <a href="{{ route('journaux.create') }}" class="btn-primaire">Nouveau journal</a>
        @endif
    </x-entete>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Code</th><th>Libellé</th><th>Type</th><th class="num">Écritures</th><th></th></tr></thead>
            <tbody>
                @foreach ($journaux as $j)
                    <tr>
                        <td class="font-semibold">{{ $j->code }}</td>
                        <td>{{ $j->libelle }}</td>
                        <td>{{ \App\Models\Journal::TYPES[$j->type] ?? $j->type }}</td>
                        <td class="num">{{ $j->ecritures_count }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('etats.journal', ['journal_id' => $j->id]) }}" class="lien text-xs">Consulter</a>
                            @if (auth()->user()->estComptable())
                                <a href="{{ route('journaux.edit', $j) }}" class="lien ml-3 text-xs">Modifier</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
