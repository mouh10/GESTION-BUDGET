{{-- Ligne d'écriture ; $i = index, $l = valeurs --}}
<tr data-ligne>
    <td class="min-w-56">
        <select name="lignes[{{ $i }}][compte_id]" class="champ-petit">
            <option value="">— Compte —</option>
            @foreach ($comptes as $c)
                <option value="{{ $c->id }}" @selected(($l['compte_id'] ?? null) == $c->id)>{{ $c->numero }} - {{ $c->libelle }}</option>
            @endforeach
        </select>
    </td>
    <td class="min-w-40">
        <select name="lignes[{{ $i }}][tiers_id]" class="champ-petit">
            <option value="">—</option>
            @foreach ($tiers as $t)
                <option value="{{ $t->id }}" @selected(($l['tiers_id'] ?? null) == $t->id)>{{ $t->nom }}</option>
            @endforeach
        </select>
    </td>
    <td class="min-w-40"><input name="lignes[{{ $i }}][libelle]" value="{{ $l['libelle'] ?? '' }}" class="champ-petit" placeholder="Libellé de ligne"></td>
    <td class="w-36"><input name="lignes[{{ $i }}][debit]" value="{{ ($l['debit'] ?? 0) > 0 ? (float) $l['debit'] : '' }}" type="number" step="0.01" min="0" class="champ-petit text-right" data-debit></td>
    <td class="w-36"><input name="lignes[{{ $i }}][credit]" value="{{ ($l['credit'] ?? 0) > 0 ? (float) $l['credit'] : '' }}" type="number" step="0.01" min="0" class="champ-petit text-right" data-credit></td>
    <td class="w-10 text-center"><button type="button" class="text-slate-400 hover:text-red-600" data-supprimer-ligne title="Supprimer la ligne">✕</button></td>
</tr>
