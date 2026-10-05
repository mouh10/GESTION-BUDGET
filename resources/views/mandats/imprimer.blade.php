@php
    $liq = $mandat->liquidation;
    $e = $liq->engagement;
    $l = $e->ligneCredit;
    $t = $e->tiers;
    $p = $mandat->paiement;
    $tampon = match ($mandat->statut) {
        'paye' => ['Payé', 'vert', $mandat->date_paiement ? 'le '.date_fr($mandat->date_paiement) : null],
        'pris_en_charge' => ['Pris en charge', 'bleu', $mandat->pris_en_charge_le ? 'le '.date_fr($mandat->pris_en_charge_le) : null],
        'rejete' => ['Rejeté', 'rouge', 'Comptable public'],
        default => ['Émis', 'orange', 'En attente de prise en charge'],
    };
@endphp
<x-document titre="Mandat de paiement" :numero="$mandat->numero" :date="$mandat->date" :service="'Ordonnateur : '.$l->service->libelle"
            :tampon="$tampon" :gestion="$e->exercice?->annee()" :reference="'Mandat '.$mandat->numero.' · engagement '.$e->numero">

    <section class="doc-section">
        <h3>Imputation budgétaire</h3>
        <div class="doc-grille">
            <div class="large"><span class="lib">Programme › action</span><span class="val">{{ $l->action->programme->intitule }} › {{ $l->action->libelle }}</span></div>
            <div><span class="lib">Nature économique</span><span class="val">{{ $l->nature->intitule }}</span></div>
            <div><span class="lib">Compte d’imputation</span><span class="val code">{{ $l->nature->compte?->numero ?? '—' }}</span></div>
            <div class="large"><span class="lib">Code d’imputation</span><span class="val code">{{ $l->imputation() }} · {{ \App\Models\LigneCredit::SOURCES[$l->source] }}</span></div>
        </div>
    </section>

    <section class="doc-section">
        <h3>Créancier</h3>
        <div class="doc-grille">
            <div><span class="lib">Nom ou raison sociale</span><span class="val">{{ $t->nom }}</span></div>
            <div><span class="lib">NINEA</span><span class="val code">{{ $t->ninea ?: '—' }}</span></div>
            <div><span class="lib">Adresse</span><span class="val">{{ $t->adresse ?: '—' }}</span></div>
            <div><span class="lib">Domiciliation (RIB)</span><span class="val code">{{ $t->rib ?: '—' }}</span></div>
        </div>
    </section>

    <section class="doc-section">
        <h3>Pièces et références</h3>
        <table class="doc-table">
            <thead><tr><th>Pièce</th><th>Numéro</th><th>Date</th><th>Détail</th><th class="num">Montant</th></tr></thead>
            <tbody>
                <tr><td>Engagement</td><td>{{ $e->numero }}</td><td>{{ date_fr($e->date) }}</td><td>{{ $e->objet }}</td><td class="num">{{ fcfa($e->montant) }}</td></tr>
                <tr><td>Liquidation</td><td>{{ $liq->numero }}</td><td>{{ date_fr($liq->date) }}</td><td>{{ $liq->reference_facture }}{{ $liq->date_service_fait ? ' · service fait le '.date_fr($liq->date_service_fait) : '' }}</td><td class="num">{{ fcfa($liq->montant) }}</td></tr>
                <tr class="fort"><td>Mandat</td><td>{{ $mandat->numero }}</td><td>{{ date_fr($mandat->date) }}</td><td>Montant ordonnancé</td><td class="num">{{ fcfa($mandat->montant) }}</td></tr>
            </tbody>
        </table>
    </section>

    <x-doc.montant :montant="$mandat->montant" libelle="Net à payer" phrase="Arrêté le présent mandat à la somme de" />

    @if ($p)
        <section class="doc-section">
            <h3>Paiement</h3>
            <div class="doc-grille">
                <div><span class="lib">Date de paiement</span><span class="val">{{ date_fr($p->date) }}</span></div>
                <div><span class="lib">Mode · référence</span><span class="val">{{ \App\Models\MouvementTresorerie::MODES[$p->mode] ?? $p->mode }}{{ $p->reference ? ' · '.$p->reference : '' }}</span></div>
                <div class="large"><span class="lib">Compte payeur</span><span class="val">{{ $p->compteTresorerie?->nom }}</span></div>
            </div>
        </section>
    @endif

    @if ($mandat->statut === 'rejete' && $mandat->motif_rejet)
        <p class="alerte-doc"><strong>Motif du rejet :</strong> {{ $mandat->motif_rejet }}</p>
    @endif

    <p class="fait-a">Fait à {{ config('gestion.entreprise.ville') }}, le {{ date_fr($mandat->date) }}</p>

    <div class="signatures">
        <x-doc.signature qui="L’ordonnateur" pied="Certifié le service fait · date et signature" />
        <x-doc.signature qui="Le comptable public"
            :mention="$mandat->pris_en_charge_le ? 'Pris en charge le '.date_fr($mandat->pris_en_charge_le) : null" pied="« Vu, bon à payer » · date et signature" />
        <x-doc.signature qui="Pour acquit" :mention="$mandat->statut === 'paye' ? 'Payé le '.date_fr($mandat->date_paiement) : null" pied="Le créancier · date et signature" />
    </div>
</x-document>
