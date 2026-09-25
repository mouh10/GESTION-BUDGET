<x-layout titre="Livre journal">
    <x-entete titre="Livre journal" :sous-titre="$exercice->libelle.' · écritures validées, dans l’ordre chronologique'" />

    <form method="GET" class="carte carte-corps no-print mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="etiquette" for="journal_id">Journal</label>
            <select id="journal_id" name="journal_id" class="champ">
                <option value="">Tous les journaux</option>
                @foreach ($journaux as $j)
                    <option value="{{ $j->id }}" @selected(request('journal_id') == $j->id)>{{ $j->code }} - {{ $j->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ"></div>
        <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Afficher</button>
        <button type="button" class="btn-secondaire" onclick="window.print()">Imprimer</button>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Date</th><th>Pièce</th><th>Compte</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th></tr></thead>
            <tbody>
                @forelse ($ecritures as $e)
                    <tr class="bg-slate-50">
                        <td class="font-medium whitespace-nowrap">{{ date_fr($e->date) }}</td>
                        <td class="whitespace-nowrap"><a href="{{ route('ecritures.show', $e) }}" class="lien font-medium">{{ $e->numero_piece }}</a></td>
                        <td colspan="4" class="font-medium">{{ $e->libelle }}</td>
                    </tr>
                    @foreach ($e->lignes as $l)
                        <tr>
                            <td></td><td></td>
                            <td class="tabular-nums {{ $l->credit > 0 ? 'pl-10' : '' }}">{{ $l->compte->numero }} <span class="text-slate-500">{{ $l->compte->libelle }}</span></td>
                            <td class="text-slate-600">{{ $l->libelle }}{{ $l->tiers ? ' · '.$l->tiers->nom : '' }}</td>
                            <td class="num">{{ $l->debit > 0 ? montant($l->debit) : '' }}</td>
                            <td class="num">{{ $l->credit > 0 ? montant($l->credit) : '' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucune écriture validée sur cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $ecritures->links() }}
    </div>
</x-layout>
