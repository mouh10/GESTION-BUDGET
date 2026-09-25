@php
    $onglets = ['' => 'Tous', 'brouillon' => 'Brouillons', 'soumis' => 'À viser', 'rejete' => 'Rejetés', 'vise' => 'Visés', 'annule' => 'Annulés'];
@endphp
<x-layout titre="Engagements">
    <x-entete titre="Engagements" sous-titre="Engagement juridique de la dépense, contrôle de disponibilité et visa du contrôle financier.">
        @if (auth()->user()->estOrdonnateur())
            <a href="{{ route('engagements.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouvel engagement</a>
        @endif
    </x-entete>

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($onglets as $v => $l)
            @php $c = $v === '' ? null : ($compteurs[$v] ?? null); @endphp
            <a href="{{ route('engagements.index', array_filter(['statut' => $v] + request()->except(['statut', 'page']))) }}" class="{{ request('statut', '') === $v ? 'btn-primaire' : 'btn-secondaire' }} btn-petit">
                {{ $l }} @if ($v !== '')<span class="opacity-70">({{ $c->n ?? 0 }})</span>@endif
            </a>
        @endforeach
    </div>

    <form method="GET" class="carte carte-corps mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <input type="hidden" name="statut" value="{{ request('statut') }}">
        <div><label class="etiquette" for="q">Recherche</label><input id="q" name="q" value="{{ request('q') }}" class="champ" placeholder="N°, objet, bénéficiaire"></div>
        <div>
            <label class="etiquette" for="programme_id">Programme</label>
            <select id="programme_id" name="programme_id" class="champ"><option value="">Tous</option>
                @foreach ($programmes as $p)<option value="{{ $p->id }}" @selected(request('programme_id') == $p->id)>{{ $p->code }} - {{ \Illuminate\Support\Str::limit($p->libelle, 40) }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="service_id">Service</label>
            <select id="service_id" name="service_id" class="champ"><option value="">Tous</option>
                @foreach ($services as $s)<option value="{{ $s->id }}" @selected(request('service_id') == $s->id)>{{ $s->code }}</option>@endforeach
            </select>
        </div>
        <div class="flex gap-2"><button class="btn-secondaire">Filtrer</button><a href="{{ route('engagements.index') }}" class="btn-secondaire">Réinitialiser</a></div>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>N°</th><th>Date</th><th>Bénéficiaire</th><th>Objet</th><th>Imputation</th><th class="num">Montant</th><th class="num">Liquidé</th><th>Statut</th></tr></thead>
            <tbody>
                @forelse ($engagements as $e)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('engagements.show', $e) }}" class="lien font-medium">{{ $e->numero }}</a></td>
                        <td class="whitespace-nowrap">{{ date_fr($e->date) }}</td>
                        <td>{{ $e->tiers->nom }}</td>
                        <td>{{ $e->objet }}<span class="block text-xs text-slate-500">{{ \App\Models\Engagement::TYPES[$e->type] }}</span></td>
                        <td class="whitespace-nowrap text-xs text-slate-600">{{ $e->ligneCredit->imputation() }}</td>
                        <td class="num">{{ montant($e->montant) }}</td>
                        <td class="num">{{ montant($e->liquide) }}</td>
                        <td><x-statut :statut="$e->statut" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">Aucun engagement.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $engagements->links() }}
    </div>
</x-layout>
