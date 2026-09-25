<tr data-ligne>
    <td class="min-w-96">
        <select name="lignes[{{ $i }}][ligne_credit_id]" class="champ-petit" required>
            <option value="">— Ligne de crédits —</option>
            @foreach ($situation as $s)
                <option value="{{ $s->ligne->id }}" @selected(($l['ligne_credit_id'] ?? null) == $s->ligne->id)>{{ $s->ligne->imputation() }} — {{ \Illuminate\Support\Str::limit($s->ligne->libelle ?: $s->nature->libelle, 40) }} (dispo. CP {{ montant($s->cp_disponible) }})</option>
            @endforeach
        </select>
    </td>
    <td class="w-44"><input name="lignes[{{ $i }}][ae]" value="{{ isset($l['ae']) && $l['ae'] != 0 ? (float) $l['ae'] : '' }}" type="number" step="1" class="champ-petit text-right" data-ae placeholder="= CP si hors invest."></td>
    <td class="w-44"><input name="lignes[{{ $i }}][cp]" value="{{ isset($l['cp']) && $l['cp'] != 0 ? (float) $l['cp'] : '' }}" type="number" step="1" class="champ-petit text-right" data-cp></td>
    <td class="w-10 text-center"><button type="button" class="text-slate-400 hover:text-red-600" data-supprimer-ligne title="Supprimer">✕</button></td>
</tr>
