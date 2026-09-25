<x-layout :titre="$engagement->exists ? 'Modifier l’engagement' : 'Nouvel engagement'">
    <x-entete :titre="$engagement->exists ? 'Engagement '.$engagement->numero : 'Nouvel engagement'"
              sous-titre="À la soumission, le montant est comparé aux AE disponibles de la ligne ; l’engagement est ensuite transmis au contrôleur financier pour visa." />

    @if ($engagement->statut === 'rejete' && $engagement->motif_rejet)
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Motif du rejet :</strong> {{ $engagement->motif_rejet }}</div>
    @endif

    <form method="POST" action="{{ $engagement->exists ? route('engagements.update', $engagement) : route('engagements.store') }}" class="carte carte-corps max-w-4xl space-y-4">
        @csrf
        @if ($engagement->exists) @method('PUT') @endif
        <div>
            <label class="etiquette" for="ligne_credit_id">Imputation budgétaire <span class="text-red-600">*</span></label>
            <select id="ligne_credit_id" name="ligne_credit_id" class="champ" required data-info-ligne="#info-ligne">
                <option value="">— Choisir la ligne de crédits —</option>
                @foreach ($situation->groupBy(fn ($s) => $s->programme->code) as $prog => $groupe)
                    <optgroup label="Programme {{ $groupe->first()->programme->intitule }}">
                        @foreach ($groupe as $s)
                            <option value="{{ $s->ligne->id }}" data-info="AE disponibles : {{ fcfa($s->ae_disponible) }} · CP disponibles : {{ fcfa($s->cp_disponible) }}" @selected(old('ligne_credit_id', $engagement->ligne_credit_id) == $s->ligne->id)>
                                {{ $s->ligne->imputation() }} — {{ $s->ligne->libelle ?: $s->nature->libelle }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="aide font-medium text-marque-700" id="info-ligne"></p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="etiquette" for="tiers_id">Bénéficiaire <span class="text-red-600">*</span></label>
                <select id="tiers_id" name="tiers_id" class="champ" required>
                    <option value="">— Choisir —</option>
                    @foreach ($beneficiaires as $t)<option value="{{ $t->id }}" @selected(old('tiers_id', $engagement->tiers_id) == $t->id)>{{ $t->nom }}</option>@endforeach
                </select>
                @if (auth()->user()->estOrdonnateur())<a href="{{ route('tiers.create', ['type' => 'fournisseur']) }}" class="lien mt-1 inline-block text-xs">+ Nouveau fournisseur</a>@endif
            </div>
            <div>
                <label class="etiquette" for="marche_id">Marché / contrat (facultatif)</label>
                <select id="marche_id" name="marche_id" class="champ">
                    <option value="">— Aucun —</option>
                    @foreach ($marches as $m)<option value="{{ $m->id }}" @selected(old('marche_id', $engagement->marche_id) == $m->id)>{{ $m->numero }} — {{ $m->tiers->nom }} (reste {{ montant($m->resteAEngager()) }})</option>@endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="etiquette" for="type">Pièce d’engagement</label>
                <select id="type" name="type" class="champ">
                    @foreach (\App\Models\Engagement::TYPES as $v => $l)<option value="{{ $v }}" @selected(old('type', $engagement->type) === $v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <x-champ nom="date" label="Date" type="date" :valeur="$engagement->date?->toDateString()" requis />
            <x-champ nom="montant" label="Montant (FCFA)" type="number" step="1" min="1" :valeur="$engagement->montant !== null ? (float) $engagement->montant : null" requis />
        </div>
        <x-champ nom="objet" label="Objet de la dépense" :valeur="$engagement->objet" requis />
        <div class="flex flex-wrap gap-2 pt-2">
            <button class="btn-secondaire" name="soumettre" value="0">Enregistrer en brouillon</button>
            <button class="btn-primaire" name="soumettre" value="1">Enregistrer et soumettre au visa</button>
            <a href="{{ $engagement->exists ? route('engagements.show', $engagement) : route('engagements.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
