<x-layout :titre="'Acte '.$modification->numero">
    <x-entete :titre="\App\Models\Modification::TYPES_COURTS[$modification->type].' '.$modification->numero" :sous-titre="($modification->reference_acte ?: 'Sans référence').' · '.date_fr($modification->date)">
        @if (! $modification->estApprouvee())
            @if (auth()->user()->estOrdonnateur())
                <a href="{{ route('modifications.edit', $modification) }}" class="btn-secondaire">Modifier</a>
                <form method="POST" action="{{ route('modifications.destroy', $modification) }}" data-confirm="Supprimer ce brouillon ?">@csrf @method('DELETE')<button class="btn-danger">Supprimer</button></form>
            @endif
            @if (auth()->user()->estAdmin())
                <form method="POST" action="{{ route('modifications.approuver', $modification) }}" data-confirm="Approuver l’acte ? Les crédits seront modifiés immédiatement.">@csrf<button class="btn-primaire">Approuver l’acte</button></form>
            @endif
        @endif
        <a href="{{ route('modifications.index') }}" class="btn-secondaire">Retour</a>
    </x-entete>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="kpi"><p class="kpi-libelle">Statut</p><p class="mt-2"><x-statut :statut="$modification->statut" /></p>@if ($modification->approuve_le)<p class="mt-1 text-xs text-slate-500">le {{ $modification->approuve_le->format('d/m/Y à H:i') }}</p>@endif</div>
        <div class="kpi"><p class="kpi-libelle">Type</p><p class="mt-2 text-sm">{{ \App\Models\Modification::TYPES[$modification->type] }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Motif</p><p class="mt-2 text-sm text-slate-600">{{ $modification->motif ?: '—' }}</p></div>
    </div>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Ligne de crédits</th><th class="num">AE</th><th class="num">CP</th><th class="num">AE disponibles</th><th class="num">CP disponibles</th></tr></thead>
            <tbody>
                @foreach ($modification->lignes as $l)
                    @php $s = $situation[$l->ligne_credit_id] ?? null; @endphp
                    <tr>
                        <td><a href="{{ route('credits.show', $l->ligneCredit) }}" class="lien font-medium">{{ $l->ligneCredit->imputation() }}</a><span class="block text-xs text-slate-500">{{ $l->ligneCredit->libelle ?: $l->ligneCredit->nature->libelle }}</span></td>
                        <td class="num {{ $l->ae < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ ($l->ae > 0 ? '+' : '').montant($l->ae) }}</td>
                        <td class="num {{ $l->cp < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ ($l->cp > 0 ? '+' : '').montant($l->cp) }}</td>
                        <td class="num">{{ $s ? montant($s->ae_disponible) : '' }}</td>
                        <td class="num">{{ $s ? montant($s->cp_disponible) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td>Total</td><td class="num">{{ montant($modification->lignes->sum('ae')) }}</td><td class="num">{{ montant($modification->lignes->sum('cp')) }}</td><td colspan="2"></td></tr></tfoot>
        </table>
    </div>
    <p class="mt-3 text-xs text-slate-500">Saisi par {{ $modification->user?->name ?? '—' }}. {{ $modification->estApprouvee() ? 'Les disponibles affichés tiennent compte de l’acte.' : 'Les disponibles affichés sont ceux d’avant approbation.' }}</p>
</x-layout>
