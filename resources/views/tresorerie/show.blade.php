<x-layout :titre="$tresorerie->nom">
    <x-entete :titre="$tresorerie->nom" :sous-titre="(\App\Models\CompteTresorerie::TYPES[$tresorerie->type] ?? '').' · compte '.$tresorerie->compte->numero.' · journal '.$tresorerie->journal->code">
        @if (auth()->user()->estComptable())
            <a href="{{ route('tresorerie.mouvements.create', [$tresorerie, 'type' => 'encaissement']) }}" class="btn-primaire">+ Encaissement</a>
            <a href="{{ route('tresorerie.mouvements.create', [$tresorerie, 'type' => 'decaissement']) }}" class="btn-secondaire">− Décaissement</a>
            <a href="{{ route('tresorerie.edit', $tresorerie) }}" class="btn-secondaire">Modifier</a>
        @endif
    </x-entete>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="kpi"><p class="kpi-libelle">Solde d'ouverture</p><p class="kpi-valeur">{{ fcfa($tresorerie->solde_initial) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Entrées</p><p class="kpi-valeur text-emerald-700">{{ fcfa($entrees) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Sorties</p><p class="kpi-valeur text-red-700">{{ fcfa($sorties) }}</p></div>
        <div class="kpi"><p class="kpi-libelle">Solde actuel</p><p class="kpi-valeur {{ $solde < 0 ? 'text-red-700' : '' }}">{{ fcfa($solde) }}</p></div>
    </div>

    <form method="GET" class="carte carte-corps mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="etiquette" for="type">Type</label>
            <select id="type" name="type" class="champ">
                <option value="">Tous</option>
                @foreach (\App\Models\MouvementTresorerie::TYPES as $v => $l)
                    <option value="{{ $v }}" @selected(request('type') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ"></div>
        <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Filtrer</button>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Date</th><th>Libellé</th><th>Contrepartie</th><th>Mode</th><th>Pièce</th><th class="num">Entrée</th><th class="num">Sortie</th><th></th></tr></thead>
            <tbody>
                @forelse ($mouvements as $m)
                    <tr>
                        <td class="whitespace-nowrap">{{ date_fr($m->date) }}</td>
                        <td>
                            {{ $m->libelle }}
                            @if ($m->mandat)<a href="{{ route('mandats.show', $m->mandat) }}" class="lien block text-xs">Mandat {{ $m->mandat->numero }}</a>@endif
                            @if ($m->titreRecette)<a href="{{ route('titres.show', $m->titreRecette) }}" class="lien block text-xs">Titre {{ $m->titreRecette->numero }}</a>@endif
                            @if ($m->reference)<span class="block text-xs text-slate-500">Réf. {{ $m->reference }}</span>@endif
                        </td>
                        <td class="text-slate-600">{{ $m->tiers?->nom ?? ($m->compte ? $m->compte->numero.' '.$m->compte->libelle : '') }}</td>
                        <td>{{ \App\Models\MouvementTresorerie::MODES[$m->mode] ?? '' }}</td>
                        <td class="whitespace-nowrap">@if ($m->ecriture)<a href="{{ route('ecritures.show', $m->ecriture) }}" class="lien text-xs">{{ $m->ecriture->numero_piece }}</a>@endif</td>
                        <td class="num text-emerald-700">{{ $m->estEncaissement() ? montant($m->montant) : '' }}</td>
                        <td class="num text-red-700">{{ $m->estEncaissement() ? '' : montant($m->montant) }}</td>
                        <td class="text-right">
                            @if (auth()->user()->estComptable())
                                <form method="POST" action="{{ route('mouvements.destroy', $m) }}" data-confirm="Annuler ce mouvement ? L'écriture sera contre-passée{{ $m->virement_id ? ' (les deux côtés du virement)' : '' }}.">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-700 hover:underline">Annuler</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $mouvements->links() }}
    </div>
</x-layout>
