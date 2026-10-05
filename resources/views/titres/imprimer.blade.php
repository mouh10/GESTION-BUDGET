@php
    $p = $titre->prevision;
    $t = $titre->tiers;
    $tampon = match ($titre->statut) {
        'recouvre' => ['Recouvré', 'vert', null],
        'partiellement_recouvre' => ['Partiel', 'bleu', 'recouvrement en cours'],
        'annule' => ['Annulé', 'gris', null],
        default => ['À recouvrer', 'orange', $titre->date_echeance ? 'échéance '.date_fr($titre->date_echeance) : null],
    };
@endphp
<x-document titre="Titre de recette" :numero="$titre->numero" :date="$titre->date" :service="$p->service?->libelle"
            :tampon="$tampon" :gestion="$titre->exercice?->annee()" :reference="'Titre '.$titre->numero.($titre->ecriture ? ' · prise en charge '.$titre->ecriture->numero_piece : '')">

    <section class="doc-section">
        <h3>Imputation</h3>
        <div class="doc-grille">
            <div><span class="lib">Nature de recette</span><span class="val">{{ $p->nature->intitule }}</span></div>
            <div><span class="lib">Compte de produit</span><span class="val code">{{ $p->nature->compte?->numero ?? '—' }}</span></div>
            <div class="large"><span class="lib">Service d’assiette</span><span class="val">{{ $p->service?->intitule ?? '—' }}</span></div>
        </div>
    </section>

    <section class="doc-section">
        <h3>Redevable et objet</h3>
        <div class="doc-grille">
            <div><span class="lib">Redevable</span><span class="val">{{ $t->nom }}</span></div>
            <div><span class="lib">Code · NINEA</span><span class="val code">{{ $t->code }}{{ $t->ninea ? ' · '.$t->ninea : '' }}</span></div>
            <div class="large"><span class="lib">Objet</span><span class="val">{{ $titre->objet }}</span></div>
            <div><span class="lib">Date d’émission</span><span class="val">{{ date_fr($titre->date) }}</span></div>
            <div><span class="lib">Date d’échéance</span><span class="val">{{ $titre->date_echeance ? date_fr($titre->date_echeance) : '—' }}</span></div>
        </div>
    </section>

    <x-doc.montant :montant="$titre->montant" libelle="Montant à recouvrer" phrase="Arrêté le présent titre à la somme de" />

    <section class="doc-section">
        <h3>Situation du recouvrement</h3>
        <table class="doc-table">
            <thead><tr><th>Date</th><th>Compte d’encaissement</th><th>Mode</th><th>Référence</th><th class="num">Montant</th></tr></thead>
            <tbody>
                @forelse ($titre->recouvrements as $r)
                    <tr><td>{{ date_fr($r->date) }}</td><td>{{ $r->compteTresorerie?->nom }}</td><td>{{ \App\Models\MouvementTresorerie::MODES[$r->mode] ?? $r->mode }}</td><td>{{ $r->reference ?: '—' }}</td><td class="num">{{ fcfa($r->montant) }}</td></tr>
                @empty
                    <tr><td colspan="5" style="color: var(--gris)">Aucun recouvrement enregistré.</td></tr>
                @endforelse
                <tr class="fort"><td colspan="4">Reste à recouvrer</td><td class="num">{{ $titre->statut === 'annule' ? '—' : fcfa($titre->reste()) }}</td></tr>
            </tbody>
        </table>
    </section>

    <p class="fait-a">Fait à {{ config('gestion.entreprise.ville') }}, le {{ date_fr($titre->date) }}</p>

    <div class="signatures">
        <x-doc.signature qui="L’ordonnateur" pied="Rendu exécutoire · date et signature" />
        <x-doc.signature qui="Le comptable public" :mention="$titre->ecriture ? 'Pris en charge le '.date_fr($titre->ecriture->date) : null" pied="Pris en charge · date et signature" />
    </div>
</x-document>
