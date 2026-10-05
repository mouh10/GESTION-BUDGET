<x-layout titre="Journal d’audit">
    <x-entete titre="Journal d’audit" sous-titre="Toutes les actions enregistrées : qui a fait quoi, quand et depuis quel poste. Le journal ne peut être ni modifié ni effacé depuis l’application." />

    <form method="GET" class="carte carte-corps mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        <div class="lg:col-span-2"><label class="etiquette" for="q">Recherche</label><input id="q" name="q" value="{{ request('q') }}" class="champ" placeholder="N° de pièce, description, adresse IP"></div>
        <div><label class="etiquette" for="user_id">Utilisateur</label>
            <select id="user_id" name="user_id" class="champ"><option value="">Tous</option>@foreach ($utilisateurs as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <div><label class="etiquette" for="action">Action</label>
            <select id="action" name="action" class="champ"><option value="">Toutes</option>@foreach (\App\Models\JournalAudit::ACTIONS as $v => $l)<option value="{{ $v }}" @selected(request('action') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="etiquette" for="type">Objet</label>
            <select id="type" name="type" class="champ"><option value="">Tous</option>@foreach (\App\Models\JournalAudit::TYPES as $v => $l)<option value="{{ $v }}" @selected(request('type') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-2">
            <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ px-2"></div>
            <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ px-2"></div>
        </div>
        <div class="flex gap-2 lg:col-span-6"><button class="btn-primaire">Filtrer</button><a href="{{ route('audit.index') }}" class="btn-secondaire">Réinitialiser</a></div>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Date et heure</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Détail</th><th>Poste (IP)</th></tr></thead>
            <tbody>
                @forelse ($entrees as $a)
                    <tr @if ($a->url()) data-href="{{ $a->url() }}" @endif>
                        <td class="whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="whitespace-nowrap">{{ $a->user?->name ?? 'Système' }}</td>
                        <td><span class="{{ match ($a->action) { 'statut' => 'badge-bleu', 'suppression', 'echec_connexion' => 'badge-rouge', 'creation', 'piece_jointe' => 'badge-vert', 'sauvegarde', 'mot_de_passe' => 'badge-orange', default => 'badge-gris' } }}">{{ $a->libelleAction() }}</span></td>
                        <td>@if ($a->url())<a href="{{ $a->url() }}" class="lien">{{ $a->sujet_libelle }}</a>@else{{ $a->sujet_libelle ?? '—' }}@endif</td>
                        <td class="text-slate-600">
                            {{ $a->description }}
                            @if (! empty($a->details['changements']) && $a->action !== 'statut')
                                <details class="mt-1 text-xs no-print"><summary class="lien cursor-pointer">Voir les changements</summary>
                                    <ul class="mt-1 space-y-0.5">
                                        @foreach ($a->details['changements'] as $champ => $c)
                                            <li><span class="font-medium">{{ $champ }}</span> : {{ is_scalar($c['avant'] ?? null) ? $c['avant'] : json_encode($c['avant'] ?? null) }} → {{ is_scalar($c['apres'] ?? null) ? $c['apres'] : json_encode($c['apres'] ?? null) }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-xs text-slate-500">{{ $a->ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucune entrée.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $entrees->links() }}
    </div>
</x-layout>
