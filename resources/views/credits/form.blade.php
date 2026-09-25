@php $titresAe = \App\Models\Nature::TITRES_AE_DISTINCTES; @endphp
<x-layout :titre="$ligne->exists ? 'Modifier la ligne' : 'Nouvelle ligne de crédits'">
    <x-entete :titre="$ligne->exists ? 'Ligne '.$ligne->imputation() : 'Nouvelle ligne de crédits'" :sous-titre="'Loi de finances initiale — '.$exercice->libelle" />

    @unless ($modifiable)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Cette ligne a déjà été exécutée ou modifiée : seul le libellé peut être changé. Les montants évoluent par un <a href="{{ route('modifications.create') }}" class="font-medium underline">acte de modification budgétaire</a>.
        </div>
    @endunless

    <form method="POST" action="{{ $ligne->exists ? route('credits.update', $ligne) : route('credits.store') }}" class="carte carte-corps max-w-3xl space-y-4">
        @csrf
        @if ($ligne->exists) @method('PUT') @endif
        <fieldset @disabled(! $modifiable) class="space-y-4">
            <div>
                <label class="etiquette" for="action_id">Programme / action <span class="text-red-600">*</span></label>
                <select id="action_id" name="action_id" class="champ" required>
                    <option value="">— Choisir —</option>
                    @foreach ($actions as $a)<option value="{{ $a->id }}" @selected(old('action_id', $ligne->action_id) == $a->id)>{{ $a->programme->code }}.{{ $a->code }} — {{ $a->programme->libelle }} › {{ $a->libelle }}</option>@endforeach
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="etiquette" for="service_id">Service gestionnaire <span class="text-red-600">*</span></label>
                    <select id="service_id" name="service_id" class="champ" required>
                        @foreach ($services as $s)<option value="{{ $s->id }}" @selected(old('service_id', $ligne->service_id) == $s->id)>{{ $s->code }} — {{ $s->libelle }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="etiquette" for="source">Source de financement</label>
                    <select id="source" name="source" class="champ">
                        @foreach (\App\Models\LigneCredit::SOURCES as $v => $l)<option value="{{ $v }}" @selected(old('source', $ligne->source) === $v)>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="etiquette" for="nature_id">Nature économique <span class="text-red-600">*</span></label>
                <select id="nature_id" name="nature_id" class="champ" required data-info-ligne="#info-nature">
                    <option value="">— Choisir —</option>
                    @foreach ($natures->groupBy('titre') as $titre => $groupe)
                        <optgroup label="Titre {{ $titre }} — {{ \App\Models\Nature::TITRES_DEPENSE[$titre] ?? '' }}">
                            @foreach ($groupe as $n)
                                <option value="{{ $n->id }}" data-ae-distinctes="{{ in_array($n->titre, $titresAe, true) ? 1 : 0 }}" data-info="{{ in_array($n->titre, $titresAe, true) ? 'Investissement / transfert en capital : AE et CP peuvent différer (engagement pluriannuel).' : 'Hors investissement : AE = CP.' }}" @selected(old('nature_id', $ligne->nature_id) == $n->id)>{{ $n->code }} — {{ $n->libelle }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="aide" id="info-nature"></p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div data-bloc-ae class="hidden">
                    <x-champ nom="ae_initiale" label="AE initiales (FCFA)" type="number" step="1" min="0" :valeur="$ligne->ae_initiale !== null ? (float) $ligne->ae_initiale : null" />
                </div>
                <x-champ nom="cp_initial" label="CP initiaux (FCFA)" type="number" step="1" min="0" :valeur="$ligne->cp_initial !== null ? (float) $ligne->cp_initial : null" requis />
            </div>
        </fieldset>
        <x-champ nom="libelle" label="Libellé particulier (facultatif)" :valeur="$ligne->libelle" placeholder="Ex. : Entretien du parc automobile" />
        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ $ligne->exists ? route('credits.show', $ligne) : route('credits.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>

    @if ($ligne->exists && $modifiable)
        <form method="POST" action="{{ route('credits.destroy', $ligne) }}" class="mt-4" data-confirm="Supprimer cette ligne ?">
            @csrf @method('DELETE')<button class="btn-danger btn-petit">Supprimer la ligne</button>
        </form>
    @endif
</x-layout>
