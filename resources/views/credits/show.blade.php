<x-layout :titre="'Ligne '.$ligne->imputation()">
    <x-entete :titre="$ligne->libelle ?: $ligne->nature->libelle" :sous-titre="$ligne->imputation().' · '.$ligne->action->programme->libelle.' › '.$ligne->action->libelle">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('engagements.create', ['ligne_credit_id' => $ligne->id]) }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Engager</a>
            <a href="{{ route('credits.edit', $ligne) }}" class="btn-secondaire">Modifier</a>
        @endif
        <a href="{{ route('credits.index') }}" class="btn-secondaire">Retour</a>
    </x-entete>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete"><h2>Situation des crédits</h2><span class="text-sm text-slate-500">{{ $ligne->service->libelle }} · {{ \App\Models\LigneCredit::SOURCES[$ligne->source] }}</span></div>
            <table class="tableau">
                <thead><tr><th></th><th class="num">AE</th><th class="num">CP</th></tr></thead>
                <tbody>
                    <tr><td>Dotation initiale (LFI)</td><td class="num">{{ montant($s->ae_initiale) }}</td><td class="num">{{ montant($s->cp_initial) }}</td></tr>
                    <tr><td>Modifications (virements, transferts, ouvertures, annulations)</td><td class="num">{{ ($s->ae_modif >= 0 ? '+' : '').montant($s->ae_modif) }}</td><td class="num">{{ ($s->cp_modif >= 0 ? '+' : '').montant($s->cp_modif) }}</td></tr>
                    <tr class="font-semibold"><td>Dotation révisée</td><td class="num">{{ montant($s->ae_revisee) }}</td><td class="num">{{ montant($s->cp_revise) }}</td></tr>
                    <tr><td>Réserve (gel)</td><td class="num">− {{ montant($s->ae_gelee) }}</td><td class="num">− {{ montant($s->cp_gele) }}</td></tr>
                    <tr><td>Engagements (soumis et visés)</td><td class="num">− {{ montant($s->engage) }}</td><td class="num text-slate-400">—</td></tr>
                    <tr><td>Mandats émis (ordonnancé)</td><td class="num text-slate-400">—</td><td class="num">− {{ montant($s->ordonnance) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="text-base"><td>Disponible</td><td class="num {{ $s->ae_disponible <= 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ montant($s->ae_disponible) }}</td><td class="num {{ $s->cp_disponible <= 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ montant($s->cp_disponible) }}</td></tr>
                </tfoot>
            </table>
        </div>
        <div class="carte carte-corps space-y-4">
            <h2 class="text-base">Taux d’exécution</h2>
            <div><p class="mb-1 text-sm text-slate-600">Engagement (sur AE)</p><x-barre :taux="$s->taux_engagement" /></div>
            <div><p class="mb-1 text-sm text-slate-600">Ordonnancement (sur CP)</p><x-barre :taux="$s->taux_ordonnancement" couleur="bg-violet-500" /></div>
            <div><p class="mb-1 text-sm text-slate-600">Paiement (sur CP)</p><x-barre :taux="$s->taux_paiement" couleur="bg-emerald-500" /></div>
            <dl class="grid grid-cols-2 gap-y-1 border-t border-slate-100 pt-3 text-sm">
                <dt class="text-slate-500">Engagé visé</dt><dd class="num">{{ montant($s->engage_vise) }}</dd>
                <dt class="text-slate-500">Liquidé</dt><dd class="num">{{ montant($s->liquide) }}</dd>
                <dt class="text-slate-500">Payé</dt><dd class="num">{{ montant($s->paye) }}</dd>
            </dl>
            <p class="text-xs text-slate-500">Imputation comptable : {{ $ligne->nature->compte?->numero ?? 'non renseignée' }}</p>
        </div>
    </div>

    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete"><h2>Engagements sur la ligne</h2></div>
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead><tr><th>N°</th><th>Date</th><th>Bénéficiaire</th><th>Objet</th><th class="num">Montant</th><th>Statut</th></tr></thead>
                <tbody>
                    @forelse ($engagements as $e)
                        <tr>
                            <td><a href="{{ route('engagements.show', $e) }}" class="lien font-medium">{{ $e->numero }}</a></td>
                            <td>{{ date_fr($e->date) }}</td><td>{{ $e->tiers->nom }}</td><td>{{ $e->objet }}</td>
                            <td class="num">{{ montant($e->montant) }}</td><td><x-statut :statut="$e->statut" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-slate-500">Aucun engagement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $engagements->links() }}
    </div>

    @if ($modifications->isNotEmpty())
        <div class="carte mt-6 overflow-hidden">
            <div class="carte-entete"><h2>Modifications budgétaires</h2></div>
            <table class="tableau">
                <thead><tr><th>Acte</th><th>Date</th><th>Type</th><th class="num">AE</th><th class="num">CP</th><th>Statut</th></tr></thead>
                <tbody>
                    @foreach ($modifications as $m)
                        <tr>
                            <td><a href="{{ route('modifications.show', $m->modification) }}" class="lien">{{ $m->modification->numero }}</a></td>
                            <td>{{ date_fr($m->modification->date) }}</td>
                            <td>{{ \App\Models\Modification::TYPES_COURTS[$m->modification->type] }}</td>
                            <td class="num">{{ montant($m->ae) }}</td><td class="num">{{ montant($m->cp) }}</td>
                            <td><x-statut :statut="$m->modification->statut" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layout>
