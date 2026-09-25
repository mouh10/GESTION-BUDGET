<x-layout titre="Modifications budgétaires">
    <x-entete titre="Modifications budgétaires" sous-titre="Virements, transferts, ouvertures et annulations de crédits, mises en réserve. Un acte ne prend effet qu’après approbation.">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('modifications.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouvel acte</a>
        @endif
    </x-entete>
    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('modifications.index') }}" class="{{ request('type') ? 'btn-secondaire' : 'btn-primaire' }} btn-petit">Tous</a>
        @foreach (\App\Models\Modification::TYPES_COURTS as $v => $l)
            <a href="{{ route('modifications.index', ['type' => $v]) }}" class="{{ request('type') === $v ? 'btn-primaire' : 'btn-secondaire' }} btn-petit">{{ $l }}</a>
        @endforeach
    </form>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>N°</th><th>Date</th><th>Type</th><th>Référence de l’acte</th><th>Motif</th><th class="num">Lignes</th><th class="num">Montant (CP)</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($modifications as $m)
                    @php $montantActe = $m->type === 'annulation' ? abs($m->lignes->sum('cp')) : $m->lignes->where('cp', '>', 0)->sum('cp'); @endphp
                    <tr>
                        <td><a href="{{ route('modifications.show', $m) }}" class="lien font-medium">{{ $m->numero }}</a></td>
                        <td class="whitespace-nowrap">{{ date_fr($m->date) }}</td>
                        <td>{{ \App\Models\Modification::TYPES_COURTS[$m->type] }}</td>
                        <td>{{ $m->reference_acte }}</td>
                        <td class="max-w-xs truncate text-slate-600">{{ $m->motif }}</td>
                        <td class="num">{{ $m->lignes_count }}</td>
                        <td class="num">{{ montant($montantActe) }}</td>
                        <td><x-statut :statut="$m->statut" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">Aucun acte de modification.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
