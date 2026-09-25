@php
    $u = auth()->user();
    $etapes = ['brouillon' => 1, 'rejete' => 1, 'soumis' => 2, 'vise' => 3, 'annule' => 0];
    $liquide = $engagement->montantLiquide();
    $paye = $engagement->montantPaye();
@endphp
<x-layout :titre="'Engagement '.$engagement->numero">
    <x-entete :titre="'Engagement '.$engagement->numero" :sous-titre="$engagement->objet">
        @if ($u->estOrdonnateur() && $engagement->estModifiable())
            <a href="{{ route('engagements.edit', $engagement) }}" class="btn-secondaire">Modifier</a>
            <form method="POST" action="{{ route('engagements.soumettre', $engagement) }}" data-confirm="Soumettre l’engagement au contrôle financier ?">@csrf<button class="btn-primaire">Soumettre au visa</button></form>
            @if ($engagement->statut === 'brouillon')
                <form method="POST" action="{{ route('engagements.destroy', $engagement) }}" data-confirm="Supprimer ce brouillon ?">@csrf @method('DELETE')<button class="btn-danger">Supprimer</button></form>
            @endif
        @endif
        @if ($u->estControleur() && $engagement->statut === 'soumis')
            <form method="POST" action="{{ route('engagements.viser', $engagement) }}" data-confirm="Apposer le visa du contrôle financier ?">@csrf<button class="btn-primaire"><x-icone nom="bouclier" class="h-4 w-4" /> Viser</button></form>
        @endif
        @if ($u->estOrdonnateur() && in_array($engagement->statut, ['soumis', 'vise']) && $liquide == 0)
            <form method="POST" action="{{ route('engagements.annuler', $engagement) }}" data-confirm="Annuler l’engagement ? Les crédits seront libérés.">@csrf<button class="btn-danger">Annuler</button></form>
        @endif
        <a href="{{ route('engagements.imprimer', $engagement) }}" target="_blank" class="btn-secondaire">Imprimer</a>
    </x-entete>

    @if ($engagement->statut === 'rejete')
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Rejeté par le contrôle financier :</strong> {{ $engagement->motif_rejet }}</div>
    @endif

    {{-- Étapes de la chaîne --}}
    @php
        $chaine = [
            ['Engagement', $engagement->statut !== 'annule'],
            ['Visa CF', $engagement->statut === 'vise'],
            ['Liquidation', $liquide > 0],
            ['Mandat', $engagement->liquidations->flatMap->mandats->where('statut', '!=', 'rejete')->isNotEmpty()],
            ['Paiement', $paye > 0],
        ];
    @endphp
    <div class="carte mb-6 flex flex-wrap items-center gap-2 p-4">
        @foreach ($chaine as $i => [$lib, $fait])
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm {{ $fait ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                <span class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold {{ $fait ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-white' }}">{{ $i + 1 }}</span>{{ $lib }}
            </span>
            @if (! $loop->last)<span class="h-px w-6 bg-slate-300"></span>@endif
        @endforeach
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="kpi"><p class="kpi-libelle">Statut</p><p class="mt-2"><x-statut :statut="$engagement->statut" /></p>
            @if ($engagement->vise_le)<p class="mt-1 text-xs text-slate-500">Visé le {{ $engagement->vise_le->format('d/m/Y') }}{{ $engagement->viseur ? ' par '.$engagement->viseur->name : '' }}</p>@endif</div>
        <div class="kpi"><p class="kpi-libelle">Montant engagé</p><p class="kpi-valeur text-2xl">{{ fcfa($engagement->montant) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Liquidé</p><p class="kpi-valeur text-2xl">{{ fcfa($liquide) }}</p><p class="text-xs text-slate-500">Reste à liquider : {{ fcfa($engagement->montant - $liquide) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Payé</p><p class="kpi-valeur text-2xl">{{ fcfa($paye) }}</p></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Liquidations et mandats --}}
            <div class="carte overflow-hidden">
                <div class="carte-entete"><h2>Liquidations et mandats</h2></div>
                <table class="tableau">
                    <thead><tr><th>Liquidation</th><th>Date</th><th>Facture / service fait</th><th class="num">Montant</th><th>Mandat</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($engagement->liquidations as $l)
                            @php $m = $l->mandats->where('statut', '!=', 'rejete')->first(); $rejetes = $l->mandats->where('statut', 'rejete'); @endphp
                            <tr class="{{ $l->statut === 'annulee' ? 'opacity-50' : '' }}">
                                <td class="font-medium">{{ $l->numero }}@if ($l->statut === 'annulee') <span class="badge-gris">Annulée</span>@endif</td>
                                <td>{{ date_fr($l->date) }}</td>
                                <td class="text-sm">{{ $l->reference_facture ?: '—' }}@if ($l->date_service_fait)<span class="block text-xs text-slate-500">Service fait le {{ date_fr($l->date_service_fait) }}</span>@endif</td>
                                <td class="num">{{ montant($l->montant) }}</td>
                                <td>
                                    @if ($m)
                                        <a href="{{ route('mandats.show', $m) }}" class="lien">{{ $m->numero }}</a> <x-statut :statut="$m->statut" />
                                    @elseif ($rejetes->isNotEmpty())
                                        <span class="badge-rouge">Mandat rejeté</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    @if ($u->estOrdonnateur() && $l->statut === 'validee' && ! $m)
                                        <form method="POST" action="{{ route('mandats.emettre', $l) }}" class="inline" data-confirm="Émettre le mandat de {{ montant($l->montant) }} FCFA ?">@csrf<button class="btn-primaire btn-petit">Émettre le mandat</button></form>
                                        <form method="POST" action="{{ route('liquidations.annuler', $l) }}" class="inline" data-confirm="Annuler cette liquidation ?">@csrf<button class="btn-danger btn-petit">Annuler</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-slate-500">Aucune liquidation.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($u->estOrdonnateur() && $engagement->statut === 'vise' && $engagement->montant - $liquide > 0)
                    <form method="POST" action="{{ route('engagements.liquider', $engagement) }}" class="grid gap-3 border-t border-slate-100 bg-slate-50/60 p-5 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                        @csrf
                        <p class="text-sm font-semibold text-slate-900 sm:col-span-2 lg:col-span-4">Liquider (constatation du service fait)</p>
                        <x-champ nom="date" label="Date" type="date" :valeur="now()->min($engagement->exercice->date_fin)->toDateString()" requis />
                        <x-champ nom="date_service_fait" label="Service fait le" type="date" />
                        <x-champ nom="reference_facture" label="N° facture / pièce" />
                        <x-champ nom="montant" label="Montant" type="number" step="1" min="1" :max="$engagement->montant - $liquide" :valeur="$engagement->montant - $liquide" requis />
                        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="mandater" value="1" class="rounded border-slate-300" checked> Émettre le mandat immédiatement</label>
                        <div class="sm:col-span-2 lg:col-span-2 lg:text-right"><button class="btn-primaire">Enregistrer la liquidation</button></div>
                    </form>
                @endif
            </div>

            @if ($u->estControleur() && $engagement->statut === 'soumis')
                <form method="POST" action="{{ route('engagements.rejeter', $engagement) }}" class="carte carte-corps space-y-3">
                    @csrf
                    <h2 class="text-base">Rejeter l’engagement</h2>
                    <x-champ nom="motif" label="Motif du rejet (transmis à l’ordonnateur)" type="textarea" requis />
                    <button class="btn-danger">Rejeter</button>
                </form>
            @endif
        </div>

        <div class="space-y-6">
            <div class="carte carte-corps text-sm">
                <h2 class="mb-3 text-base">Imputation</h2>
                <dl class="space-y-2">
                    <div><dt class="text-slate-500">Programme</dt><dd class="font-medium">{{ $engagement->ligneCredit->action->programme->intitule }}</dd></div>
                    <div><dt class="text-slate-500">Action</dt><dd>{{ $engagement->ligneCredit->action->intitule }}</dd></div>
                    <div><dt class="text-slate-500">Service</dt><dd>{{ $engagement->ligneCredit->service->intitule }}</dd></div>
                    <div><dt class="text-slate-500">Nature</dt><dd>{{ $engagement->ligneCredit->nature->intitule }} (T{{ $engagement->ligneCredit->nature->titre }})</dd></div>
                    <div><dt class="text-slate-500">Source</dt><dd>{{ \App\Models\LigneCredit::SOURCES[$engagement->ligneCredit->source] }}</dd></div>
                </dl>
                <a href="{{ route('credits.show', $engagement->ligneCredit) }}" class="lien mt-3 inline-block">Voir la ligne</a>
                <div class="mt-4 rounded-xl bg-slate-50 p-3">
                    <p>AE disponibles : <strong>{{ fcfa($s->ae_disponible) }}</strong></p>
                    <p>CP disponibles : <strong>{{ fcfa($s->cp_disponible) }}</strong></p>
                </div>
            </div>
            <div class="carte carte-corps text-sm">
                <h2 class="mb-3 text-base">Bénéficiaire</h2>
                <a href="{{ route('tiers.show', $engagement->tiers) }}" class="lien font-medium">{{ $engagement->tiers->nom }}</a>
                <p class="text-slate-600">{{ $engagement->tiers->adresse }}</p>
                @if ($engagement->tiers->ninea)<p class="text-slate-500">NINEA {{ $engagement->tiers->ninea }}</p>@endif
                @if ($engagement->marche)<p class="mt-3">Marché : <a href="{{ route('marches.show', $engagement->marche) }}" class="lien">{{ $engagement->marche->numero }}</a></p>@endif
                <p class="mt-3 text-slate-500">Pièce : {{ \App\Models\Engagement::TYPES[$engagement->type] }} · saisi par {{ $engagement->user?->name ?? '—' }}</p>
            </div>
        </div>
    </div>
</x-layout>
