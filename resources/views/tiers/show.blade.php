<x-layout :titre="$tiers->nom">
    <x-entete :titre="$tiers->nom" :sous-titre="\App\Models\Tiers::TYPES[$tiers->type].' · '.$tiers->code.' · compte '.$tiers->compte->numero">
        @if (auth()->user()->estOrdonnateur())
            @if ($tiers->estRedevable())
                <a href="{{ route('titres.create') }}" class="btn-primaire">Émettre un titre</a>
            @else
                <a href="{{ route('engagements.create') }}" class="btn-primaire">Nouvel engagement</a>
            @endif
            <a href="{{ route('tiers.edit', $tiers) }}" class="btn-secondaire">Modifier</a>
        @endif
        <a href="{{ route('tiers.index', ['type' => $tiers->type]) }}" class="btn-secondaire">Retour</a>
    </x-entete>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="kpi"><p class="kpi-libelle">{{ $tiers->estRedevable() ? 'Reste à recouvrer' : 'Mandats non encore payés' }}</p><p class="kpi-valeur">{{ fcfa($reste) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Solde comptable ({{ $exercice->libelle }})</p><p class="kpi-valeur">{{ fcfa(abs($solde)) }}</p><p class="text-xs text-slate-500">{{ $solde > 0 ? 'Débiteur' : ($solde < 0 ? 'Créditeur' : 'Soldé') }}</p></div>
        <div class="kpi text-sm"><p class="kpi-libelle">Coordonnées</p><p class="mt-1">{{ $tiers->adresse ?: '—' }}</p><p>{{ $tiers->telephone }} {{ $tiers->email ? '· '.$tiers->email : '' }}</p>
            @if ($tiers->ninea)<p class="text-slate-500">NINEA {{ $tiers->ninea }}</p>@endif @if ($tiers->rib)<p class="text-slate-500">RIB {{ $tiers->rib }}</p>@endif</div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="carte overflow-x-auto lg:col-span-2">
            @if ($tiers->estRedevable())
                <div class="carte-entete"><h2>Titres de recette</h2></div>
                <table class="tableau">
                    <thead><tr><th>N°</th><th>Date</th><th>Objet</th><th class="num">Montant</th><th class="num">Recouvré</th><th>Statut</th></tr></thead>
                    <tbody>
                        @forelse ($titres as $t)
                            <tr><td><a href="{{ route('titres.show', $t) }}" class="lien font-medium">{{ $t->numero }}</a></td><td>{{ date_fr($t->date) }}</td><td>{{ $t->objet }}</td><td class="num">{{ montant($t->montant) }}</td><td class="num">{{ montant($t->montant_recouvre) }}</td><td><x-statut :statut="$t->statut" /></td></tr>
                        @empty
                            <tr><td colspan="6" class="text-slate-500">Aucun titre.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <div class="carte-entete"><h2>Engagements</h2></div>
                <table class="tableau">
                    <thead><tr><th>N°</th><th>Date</th><th>Objet</th><th class="num">Montant</th><th class="num">Liquidé</th><th>Statut</th></tr></thead>
                    <tbody>
                        @forelse ($engagements as $e)
                            <tr><td><a href="{{ route('engagements.show', $e) }}" class="lien font-medium">{{ $e->numero }}</a></td><td>{{ date_fr($e->date) }}</td><td>{{ $e->objet }}</td><td class="num">{{ montant($e->montant) }}</td><td class="num">{{ montant($e->liquide) }}</td><td><x-statut :statut="$e->statut" /></td></tr>
                        @empty
                            <tr><td colspan="6" class="text-slate-500">Aucun engagement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
        <div class="carte">
            <div class="carte-entete"><h2>{{ $tiers->estRedevable() ? 'Recouvrements' : 'Paiements' }}</h2></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($mouvements as $m)
                    <li class="px-6 py-3">
                        <div class="flex justify-between"><span>{{ date_fr($m->date) }}</span><span class="num font-medium">{{ fcfa($m->montant) }}</span></div>
                        <p class="text-xs text-slate-500">{{ $m->compteTresorerie->nom }}{{ $m->mandat ? ' · mandat '.$m->mandat->numero : '' }}{{ $m->titreRecette ? ' · titre '.$m->titreRecette->numero : '' }}</p>
                    </li>
                @empty
                    <li class="px-6 py-3 text-slate-500">Aucun mouvement.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layout>
