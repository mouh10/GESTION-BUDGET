@php
    $lignes = old('lignes', $lignes);
@endphp
<x-layout :titre="$ecriture->exists ? 'Modifier l’écriture' : 'Nouvelle écriture'">
    <x-entete :titre="$ecriture->exists ? 'Modifier '.$ecriture->numero_piece : 'Nouvelle écriture'"
              sous-titre="Saisie en partie double : le total des débits doit être égal au total des crédits." />

    <form method="POST" action="{{ $ecriture->exists ? route('ecritures.update', $ecriture) : route('ecritures.store') }}">
        @csrf
        @if ($ecriture->exists) @method('PUT') @endif

        <div class="carte carte-corps mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="etiquette" for="journal_id">Journal <span class="text-red-600">*</span></label>
                <select id="journal_id" name="journal_id" class="champ" required>
                    @foreach ($journaux as $j)
                        <option value="{{ $j->id }}" @selected(old('journal_id', $ecriture->journal_id) == $j->id)>{{ $j->code }} - {{ $j->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <x-champ nom="date" label="Date" type="date" :valeur="$ecriture->date?->toDateString()" requis />
            <x-champ nom="libelle" label="Libellé" :valeur="$ecriture->libelle" requis />
            <x-champ nom="reference" label="Référence / n° de pièce justificative" :valeur="$ecriture->reference" />
        </div>

        <div class="carte" data-lignes="ecriture" data-minimum="2">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Compte</th><th>Tiers</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th><th></th></tr></thead>
                    <tbody data-lignes-corps>
                        @foreach (array_values($lignes) as $i => $l)
                            @include('ecritures._ligne', ['i' => $i, 'l' => $l])
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" class="btn-secondaire btn-petit" data-ajouter-ligne>+ Ajouter une ligne</button>
                                    <button type="button" class="btn-secondaire btn-petit" data-equilibrer>Équilibrer</button>
                                    <span data-ecart class="badge-gris">—</span>
                                </div>
                            </td>
                            <td class="num" data-total-debit>0</td>
                            <td class="num" data-total-credit>0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <template>@include('ecritures._ligne', ['i' => '__INDEX__', 'l' => []])</template>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="valider" value="1" class="rounded border-slate-300" @checked(old('valider'))>
                Valider directement (l'écriture ne sera plus modifiable)
            </label>
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ $ecriture->exists ? route('ecritures.show', $ecriture) : route('ecritures.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
