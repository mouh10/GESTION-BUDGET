@php
    $l = $engagement->ligneCredit;
    $t = $engagement->tiers;
    $compte = in_array($engagement->statut, ['soumis', 'vise'], true);   // déjà compté dans la consommation des AE
    $anterieurs = $s->engage - ($compte ? (float) $engagement->montant : 0);
    $apres = $s->ae_disponible - ($compte ? 0 : (float) $engagement->montant);
    $tampon = match ($engagement->statut) {
        'vise' => ['Visé', 'vert', 'Contrôle financier · '.$engagement->vise_le?->format('d/m/Y')],
        'rejete' => ['Rejeté', 'rouge', 'Contrôle financier'],
        'annule' => ['Annulé', 'gris', null],
        'soumis' => ['En attente de visa', 'orange', null],
        default => ['Projet', 'gris', 'Non soumis au visa'],
    };
@endphp
<x-document titre="Bon d’engagement" :numero="$engagement->numero" :date="$engagement->date" :service="$l->service->libelle"
            :tampon="$tampon" :gestion="$engagement->exercice?->annee()" :reference="'Engagement '.$engagement->numero.' · imputation '.$l->imputation()">

    <section class="doc-section">
        <h3>Imputation budgétaire</h3>
        <div class="doc-grille">
            <div class="large"><span class="lib">Programme</span><span class="val">{{ $l->action->programme->intitule }}</span></div>
            <div><span class="lib">Action</span><span class="val">{{ $l->action->intitule }}</span></div>
            <div><span class="lib">Service gestionnaire</span><span class="val">{{ $l->service->intitule }}</span></div>
            <div><span class="lib">Nature économique</span><span class="val">{{ $l->nature->intitule }}</span></div>
            <div><span class="lib">Titre · source de financement</span><span class="val">Titre {{ $l->nature->titre }} · {{ \App\Models\LigneCredit::SOURCES[$l->source] }}</span></div>
            <div class="large"><span class="lib">Code d’imputation</span><span class="val code">{{ $l->imputation() }}</span></div>
        </div>
    </section>

    <section class="doc-section">
        <h3>Bénéficiaire et objet</h3>
        <div class="doc-grille">
            <div><span class="lib">Bénéficiaire</span><span class="val">{{ $t->nom }}</span></div>
            <div><span class="lib">NINEA · code</span><span class="val code">{{ $t->ninea ?: '—' }} · {{ $t->code }}</span></div>
            @if ($t->adresse || $t->rib)
                <div><span class="lib">Adresse</span><span class="val">{{ $t->adresse ?: '—' }}</span></div>
                <div><span class="lib">RIB</span><span class="val code">{{ $t->rib ?: '—' }}</span></div>
            @endif
            <div class="large"><span class="lib">Objet de la dépense</span><span class="val">{{ $engagement->objet }}</span></div>
            <div><span class="lib">Pièce justificative</span><span class="val">{{ \App\Models\Engagement::TYPES[$engagement->type] }}</span></div>
            <div><span class="lib">Marché / contrat</span><span class="val">{{ $engagement->marche ? $engagement->marche->numero : '—' }}</span></div>
        </div>
    </section>

    <section class="doc-section">
        <h3>Situation des autorisations d’engagement</h3>
        <table class="doc-table">
            <tbody>
                <tr><td>AE ouvertes (dotation révisée)</td><td class="num">{{ fcfa($s->ae_revisee) }}</td></tr>
                @if ($s->ae_gelee > 0)<tr><td>AE mises en réserve (gel)</td><td class="num">− {{ fcfa($s->ae_gelee) }}</td></tr>@endif
                <tr><td>Engagements antérieurs</td><td class="num">− {{ fcfa($anterieurs) }}</td></tr>
                <tr class="fort"><td>Disponible avant le présent engagement</td><td class="num">{{ fcfa($apres + (float) $engagement->montant) }}</td></tr>
                <tr class="accent"><td>Présent engagement</td><td class="num">− {{ fcfa($engagement->montant) }}</td></tr>
                <tr class="fort"><td>Disponible après engagement</td><td class="num">{{ fcfa($apres) }}</td></tr>
            </tbody>
        </table>
    </section>

    <x-doc.montant :montant="$engagement->montant" phrase="Arrêté le présent bon à la somme de" />

    @if ($engagement->statut === 'rejete' && $engagement->motif_rejet)
        <p class="alerte-doc"><strong>Motif du rejet :</strong> {{ $engagement->motif_rejet }}</p>
    @endif

    <p class="fait-a">Fait à {{ config('gestion.entreprise.ville') }}, le {{ date_fr($engagement->date) }}</p>

    <div class="signatures">
        <x-doc.signature qui="L’ordonnateur" :mention="$engagement->soumis_le ? 'Soumis le '.$engagement->soumis_le->format('d/m/Y') : null" />
        <x-doc.signature qui="Visa du contrôleur financier"
            :mention="$engagement->statut === 'vise' ? 'Visé le '.$engagement->vise_le?->format('d/m/Y').($engagement->viseur ? ' — '.$engagement->viseur->name : '') : null" />
    </div>
</x-document>
