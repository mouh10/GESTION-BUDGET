<x-layout :titre="$tresorerie->exists ? $tresorerie->nom : 'Nouveau compte de trésorerie'">
    <x-entete :titre="$tresorerie->exists ? 'Modifier '.$tresorerie->nom : 'Nouveau compte de trésorerie'" />

    <form method="POST" action="{{ $tresorerie->exists ? route('tresorerie.update', $tresorerie) : route('tresorerie.store') }}" class="carte carte-corps max-w-2xl space-y-4">
        @csrf
        @if ($tresorerie->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-champ nom="nom" label="Nom" :valeur="$tresorerie->nom" requis placeholder="Ex. : Banque principale" />
            <div>
                <label class="etiquette" for="type">Type</label>
                <select id="type" name="type" class="champ">
                    @foreach (\App\Models\CompteTresorerie::TYPES as $v => $l)
                        <option value="{{ $v }}" @selected(old('type', $tresorerie->type) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-champ nom="numero" label="IBAN / n° de compte / n° de téléphone" :valeur="$tresorerie->numero" />
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="etiquette" for="compte_id">Compte comptable (classe 5)</label>
                <select id="compte_id" name="compte_id" class="champ" @disabled($tresorerie->exists)>
                    @foreach ($comptes as $c)
                        <option value="{{ $c->id }}" @selected(old('compte_id', $tresorerie->compte_id) == $c->id)>{{ $c->numero }} - {{ $c->libelle }}</option>
                    @endforeach
                </select>
                <p class="aide">521 Banques · 571 Caisse · 552 Monnaie électronique. Créez un sous-compte (ex. 5211) par banque si besoin.</p>
            </div>
            <div>
                <label class="etiquette" for="journal_id">Journal</label>
                <select id="journal_id" name="journal_id" class="champ">
                    @foreach ($journaux as $j)
                        <option value="{{ $j->id }}" @selected(old('journal_id', $tresorerie->journal_id) == $j->id)>{{ $j->code }} - {{ $j->libelle }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($tresorerie->exists)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="actif" value="1" class="rounded border-slate-300" @checked(old('actif', $tresorerie->actif))> Compte actif
            </label>
        @else
            <fieldset class="rounded-lg border border-slate-200 p-4">
                <legend class="px-1 text-sm font-medium">Solde d'ouverture</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-champ nom="solde_initial" label="Montant" type="number" step="0.01" :valeur="0" />
                    <x-champ nom="date_ouverture" label="Date" type="date" :valeur="now()->toDateString()" />
                    <div>
                        <label class="etiquette" for="contrepartie_id">Contrepartie</label>
                        <select id="contrepartie_id" name="contrepartie_id" class="champ">
                            @foreach ($contreparties as $c)
                                <option value="{{ $c->id }}" @selected(old('contrepartie_id') == $c->id || (! old('contrepartie_id') && $c->numero === config('gestion.comptes.capital')))>{{ $c->numero }} - {{ $c->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="aide">Si le montant n'est pas nul, une écriture d'ouverture est passée au journal des opérations diverses.</p>
            </fieldset>
        @endif

        <div class="flex gap-2">
            <button class="btn-primaire">Enregistrer</button>
            <a href="{{ $tresorerie->exists ? route('tresorerie.show', $tresorerie) : route('tresorerie.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
