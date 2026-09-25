<x-layout :titre="'Titre '.$titre->numero">
    <x-entete :titre="'Titre de recette '.$titre->numero" :sous-titre="$titre->tiers->nom.' — '.$titre->objet">
        @if (auth()->user()->estComptable() && $titre->peutEtreRecouvre())
            <a href="{{ route('titres.recouvrement', $titre) }}" class="btn-primaire">Enregistrer un recouvrement</a>
        @endif
        @if (auth()->user()->estOrdonnateur() && $titre->statut === 'emis' && (float) $titre->montant_recouvre == 0)
            <form method="POST" action="{{ route('titres.annuler', $titre) }}" data-confirm="Annuler ce titre ? L’écriture de prise en charge sera contre-passée.">@csrf<button class="btn-danger">Annuler le titre</button></form>
        @endif
    </x-entete>
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="kpi"><p class="kpi-libelle">Statut</p><p class="mt-2"><x-statut :statut="$titre->statut" /></p></div>
        <div class="kpi"><p class="kpi-libelle">Montant</p><p class="kpi-valeur text-2xl">{{ fcfa($titre->montant) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Recouvré</p><p class="kpi-valeur text-2xl">{{ fcfa($titre->montant_recouvre) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Reste à recouvrer</p><p class="kpi-valeur text-2xl">{{ $titre->statut === 'annule' ? '—' : fcfa($titre->reste()) }}</p></div>
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="carte carte-corps text-sm">
            <h2 class="mb-3 text-base">Détail</h2>
            <p><span class="text-slate-500">Nature :</span> {{ $titre->prevision->nature->intitule }}</p>
            <p><span class="text-slate-500">Service :</span> {{ $titre->prevision->service?->libelle ?? '—' }}</p>
            <p><span class="text-slate-500">Émis le :</span> {{ date_fr($titre->date) }}{{ $titre->date_echeance ? ' · échéance '.date_fr($titre->date_echeance) : '' }}</p>
            <p><span class="text-slate-500">Redevable :</span> <a href="{{ route('tiers.show', $titre->tiers) }}" class="lien">{{ $titre->tiers->nom }}</a></p>
            @if ($titre->ecriture)<p class="mt-2"><span class="text-slate-500">Prise en charge :</span> <a href="{{ route('ecritures.show', $titre->ecriture) }}" class="lien">{{ $titre->ecriture->numero_piece }}</a></p>@endif
        </div>
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete"><h2>Recouvrements</h2></div>
            <table class="tableau">
                <thead><tr><th>Date</th><th>Compte</th><th>Mode</th><th class="num">Montant</th><th></th></tr></thead>
                <tbody>
                    @forelse ($titre->recouvrements as $r)
                        <tr>
                            <td>{{ date_fr($r->date) }}</td><td>{{ $r->compteTresorerie->nom }}</td><td>{{ \App\Models\MouvementTresorerie::MODES[$r->mode] ?? '' }} {{ $r->reference }}</td>
                            <td class="num">{{ montant($r->montant) }}</td>
                            <td class="text-right">@if (auth()->user()->estComptable())<form method="POST" action="{{ route('mouvements.destroy', $r) }}" data-confirm="Annuler ce recouvrement ?">@csrf @method('DELETE')<button class="text-xs text-red-700 hover:underline">Annuler</button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-slate-500">Aucun recouvrement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
