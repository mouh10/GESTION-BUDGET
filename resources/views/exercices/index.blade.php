<x-layout titre="Exercices">
    <x-entete titre="Exercices comptables" sous-titre="Périodes comptables. La clôture verrouille les écritures et génère les à-nouveaux sur l'exercice suivant.">
        @if (auth()->user()->estAdmin())
            <a href="{{ route('exercices.create') }}" class="btn-primaire">Nouvel exercice</a>
        @endif
    </x-entete>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Libellé</th><th>Début</th><th>Fin</th><th class="num">Écritures</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse ($exercices as $ex)
                    <tr>
                        <td class="font-medium">{{ $ex->libelle }}</td>
                        <td>{{ date_fr($ex->date_debut) }}</td>
                        <td>{{ date_fr($ex->date_fin) }}</td>
                        <td class="num">{{ $ex->ecritures_count }}</td>
                        <td><span class="{{ $ex->cloture ? 'badge-gris' : 'badge-vert' }}">{{ $ex->cloture ? 'Clôturé' : 'Ouvert' }}</span></td>
                        <td class="text-right">
                            @if (auth()->user()->estAdmin() && ! $ex->cloture)
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('exercices.edit', $ex) }}" class="btn-secondaire btn-petit">Modifier</a>
                                    <form method="POST" action="{{ route('exercices.cloturer', $ex) }}" data-confirm="Clôturer {{ $ex->libelle }} ? Plus aucune écriture ne pourra y être passée. Les à-nouveaux seront générés sur l'exercice suivant s'il existe.">
                                        @csrf
                                        <input type="hidden" name="a_nouveaux" value="1">
                                        <button class="btn-danger btn-petit">Clôturer</button>
                                    </form>
                                    @if ($ex->ecritures_count === 0)
                                        <form method="POST" action="{{ route('exercices.destroy', $ex) }}" data-confirm="Supprimer cet exercice ?">
                                            @csrf @method('DELETE')
                                            <button class="btn-danger btn-petit">Supprimer</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucun exercice.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-slate-500">Conseil : créez l'exercice suivant avant de clôturer, pour que les soldes de bilan et le résultat y soient reportés automatiquement.</p>
</x-layout>
