<x-layout titre="Écritures">
    <x-entete titre="Écritures comptables" sous-titre="Toutes les pièces de l'exercice, manuelles et automatiques.">
        @if (auth()->user()->estComptable())
            <a href="{{ route('ecritures.create') }}" class="btn-primaire">Nouvelle écriture</a>
        @endif
    </x-entete>

    <form method="GET" class="carte carte-corps mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        <div class="lg:col-span-2">
            <label class="etiquette" for="q">Recherche</label>
            <input id="q" name="q" value="{{ request('q') }}" class="champ" placeholder="Libellé, pièce, référence">
        </div>
        <div>
            <label class="etiquette" for="journal_id">Journal</label>
            <select id="journal_id" name="journal_id" class="champ">
                <option value="">Tous</option>
                @foreach ($journaux as $j)
                    <option value="{{ $j->id }}" @selected(request('journal_id') == $j->id)>{{ $j->code }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="statut">Statut</label>
            <select id="statut" name="statut" class="champ">
                <option value="">Tous</option>
                @foreach (\App\Models\Ecriture::STATUTS as $v => $l)
                    <option value="{{ $v }}" @selected(request('statut') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ"></div>
        <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <div class="flex gap-2 lg:col-span-6">
            <button class="btn-secondaire">Filtrer</button>
            <a href="{{ route('ecritures.index') }}" class="btn-secondaire">Réinitialiser</a>
        </div>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Date</th><th>Pièce</th><th>Jnl</th><th>Libellé</th><th class="num">Montant</th><th>Origine</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($ecritures as $e)
                    <tr>
                        <td class="whitespace-nowrap">{{ date_fr($e->date) }}</td>
                        <td class="whitespace-nowrap"><a href="{{ route('ecritures.show', $e) }}" class="lien font-medium">{{ $e->numero_piece }}</a></td>
                        <td>{{ $e->journal->code }}</td>
                        <td>{{ $e->libelle }}</td>
                        <td class="num">{{ montant($e->totalDebit()) }}</td>
                        <td class="text-xs text-slate-500">{{ $e->estAutomatique() ? 'Automatique' : 'Saisie' }}</td>
                        <td><span class="{{ $e->estValidee() ? 'badge-vert' : 'badge-gris' }}">{{ \App\Models\Ecriture::STATUTS[$e->statut] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-slate-500">Aucune écriture pour ces critères.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $ecritures->links() }}
    </div>
</x-layout>
