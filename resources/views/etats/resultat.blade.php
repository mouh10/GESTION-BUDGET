<x-layout titre="Compte de résultat">
    <x-entete titre="Compte de résultat" :sous-titre="$exercice->libelle.' · présentation simplifiée SYSCOHADA'" />

    <form method="GET" class="carte carte-corps no-print mb-4 flex flex-wrap items-end gap-3">
        <div><label class="etiquette" for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}" class="champ"></div>
        <div><label class="etiquette" for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}" class="champ"></div>
        <button class="btn-secondaire">Afficher</button>
        <button type="button" class="btn-secondaire" onclick="window.print()">Imprimer</button>
    </form>

    <div class="space-y-4">
        @foreach ($cr['sections'] as $section)
            <div class="carte overflow-x-auto">
                <div class="carte-entete"><h2 class="text-base">{{ $section['titre'] }}</h2></div>
                <div class="grid md:grid-cols-2 md:divide-x divide-slate-200">
                    @foreach (['produits' => 'Produits', 'charges' => 'Charges'] as $cle => $titre)
                        <table class="tableau">
                            <thead><tr><th>{{ $titre }}</th><th class="num">Montant</th></tr></thead>
                            <tbody>
                                @forelse ($section[$cle] as $r)
                                    <tr><td><span class="tabular-nums text-slate-500">{{ $r['numero'] }}</span> {{ $r['libelle'] }}</td><td class="num">{{ montant($r['montant']) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-slate-400">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endforeach
                </div>
                <div class="flex justify-between border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
                    <span>{{ $section['libelle_resultat'] }}</span>
                    <span class="tabular-nums {{ $section['resultat'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ fcfa($section['resultat']) }}</span>
                </div>
            </div>
        @endforeach

        <div class="carte">
            <table class="tableau">
                <tbody>
                    <tr><td>Résultat des activités ordinaires (exploitation + financier)</td><td class="num font-medium">{{ fcfa($cr['resultat_activites_ordinaires']) }}</td></tr>
                    <tr><td>Résultat hors activités ordinaires</td><td class="num font-medium">{{ fcfa($cr['sections']['hao']['resultat']) }}</td></tr>
                    <tr><td>Participation des travailleurs (87)</td><td class="num">− {{ montant($cr['participation']) }}</td></tr>
                    <tr><td>Impôts sur le résultat (89)</td><td class="num">− {{ montant($cr['impots']) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="text-base">
                        <td>Résultat net de l'exercice {{ $cr['resultat_net'] >= 0 ? '(bénéfice)' : '(perte)' }}</td>
                        <td class="num {{ $cr['resultat_net'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ fcfa($cr['resultat_net']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-xs text-slate-500">Total produits : {{ fcfa($cr['total_produits']) }} · Total charges : {{ fcfa($cr['total_charges']) }}</p>
    </div>
</x-layout>
