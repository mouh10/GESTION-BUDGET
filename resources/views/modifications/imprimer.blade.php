@php
    $m = $modification;
    $intitules = [
        'virement' => 'Acte de virement de crédits', 'transfert' => 'Acte de transfert de crédits', 'ouverture' => 'Acte d’ouverture de crédits',
        'annulation' => 'Acte d’annulation de crédits', 'gel' => 'Acte de mise en réserve', 'degel' => 'Acte de levée de réserve',
    ];
    $tampon = $m->estApprouvee() ? ['Approuvé', 'vert', $m->approuve_le ? 'le '.$m->approuve_le->format('d/m/Y') : null] : ['Projet', 'gris', 'En attente d’approbation'];
    $signe = in_array($m->type, ['gel', 'degel'], true);
@endphp
<x-document :titre="$intitules[$m->type] ?? 'Modification budgétaire'" :numero="$m->numero" :date="$m->date" :tampon="$tampon"
            :gestion="$m->exercice?->annee()" :reference="'Acte '.$m->numero.($m->reference_acte ? ' · '.$m->reference_acte : '')">

    <section class="doc-section">
        <h3>Objet de l’acte</h3>
        <div class="doc-grille">
            <div><span class="lib">Nature de l’opération</span><span class="val">{{ \App\Models\Modification::TYPES[$m->type] }}</span></div>
            <div><span class="lib">Texte de référence</span><span class="val">{{ $m->reference_acte ?: '—' }}</span></div>
            <div class="large"><span class="lib">Motif</span><span class="val">{{ $m->motif ?: '—' }}</span></div>
        </div>
    </section>

    <section class="doc-section">
        <h3>Lignes de crédits concernées</h3>
        <table class="doc-table">
            <thead><tr><th>Imputation</th><th>Libellé</th><th class="num">AE</th><th class="num">CP</th></tr></thead>
            <tbody>
                @foreach ($m->lignes as $ml)
                    @php $lc = $ml->ligneCredit; @endphp
                    <tr>
                        <td style="white-space: nowrap; font-variant-numeric: tabular-nums">{{ $lc->imputation() }}</td>
                        <td>{{ $lc->nature->libelle }}<br><span style="color: var(--gris); font-size: .82rem">{{ $lc->action->programme->code }} · {{ $lc->action->libelle }} · {{ $lc->service->code }}</span></td>
                        <td class="num">{{ ($signe || $ml->ae >= 0 ? '' : '− ').fcfa(abs($ml->ae)) }}</td>
                        <td class="num">{{ ($signe || $ml->cp >= 0 ? '' : '− ').fcfa(abs($ml->cp)) }}</td>
                    </tr>
                @endforeach
                <tr class="fort"><td colspan="2">Total</td><td class="num">{{ fcfa($m->lignes->sum('ae')) }}</td><td class="num">{{ fcfa($m->lignes->sum('cp')) }}</td></tr>
            </tbody>
        </table>
        @if (in_array($m->type, ['virement', 'transfert'], true))
            <p class="note">Les montants négatifs sont prélevés sur la ligne d’origine, les montants positifs ouverts sur la ligne bénéficiaire ; l’opération est équilibrée (total nul).</p>
        @endif
    </section>

    <p class="fait-a">Fait à {{ config('gestion.entreprise.ville') }}, le {{ date_fr($m->date) }}</p>

    <div class="signatures">
        <x-doc.signature qui="Le gestionnaire de crédits" :mention="$m->user ? 'Préparé par '.$m->user->name : null" />
        <x-doc.signature qui="L’autorité d’approbation" :mention="$m->estApprouvee() ? 'Approuvé le '.$m->approuve_le?->format('d/m/Y') : null" />
    </div>
</x-document>
