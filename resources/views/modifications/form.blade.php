@php $lignes = old('lignes', $lignes); @endphp
<x-layout :titre="$modification->exists ? 'Modifier l’acte' : 'Nouvel acte de modification'">
    <x-entete :titre="$modification->exists ? 'Acte '.$modification->numero : 'Nouvel acte de modification'"
              sous-titre="Montants positifs = augmentation, négatifs = diminution. Pour un gel ou un dégel, saisissez le montant mis en réserve (ou libéré) en positif." />

    <form method="POST" action="{{ $modification->exists ? route('modifications.update', $modification) : route('modifications.store') }}">
        @csrf
        @if ($modification->exists) @method('PUT') @endif
        <div class="carte carte-corps mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="etiquette" for="type">Type d’acte</label>
                <select id="type" name="type" class="champ">
                    @foreach (\App\Models\Modification::TYPES as $v => $l)<option value="{{ $v }}" @selected(old('type', $modification->type) === $v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <x-champ nom="date" label="Date" type="date" :valeur="$modification->date?->toDateString()" requis />
            <x-champ nom="reference_acte" label="Référence (arrêté, décret, LFR)" :valeur="$modification->reference_acte" />
            <x-champ nom="motif" label="Motif" type="textarea" :valeur="$modification->motif" class="sm:col-span-2 lg:col-span-4" />
        </div>

        <div class="carte" data-lignes="modification" data-minimum="2">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Ligne de crédits</th><th class="num">AE</th><th class="num">CP</th><th></th></tr></thead>
                    <tbody data-lignes-corps>
                        @foreach (array_values($lignes) as $i => $l)
                            @include('modifications._ligne', ['i' => $i, 'l' => $l])
                        @endforeach
                    </tbody>
                    <tfoot><tr><td><button type="button" class="btn-secondaire btn-petit" data-ajouter-ligne>+ Ajouter une ligne</button><span class="ml-3 text-xs font-normal text-slate-500">Un virement ou un transfert doit totaliser 0.</span></td><td class="num" data-total-ae>0</td><td class="num" data-total-cp>0</td><td></td></tr></tfoot>
                </table>
            </div>
            <template>@include('modifications._ligne', ['i' => '__INDEX__', 'l' => []])</template>
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primaire">Enregistrer le brouillon</button>
            <a href="{{ $modification->exists ? route('modifications.show', $modification) : route('modifications.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-layout>
