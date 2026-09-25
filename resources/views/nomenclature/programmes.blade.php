<x-layout titre="Programmes et actions">
    <x-entete titre="Programmes et actions" sous-titre="Classification programmatique du budget (budget-programme).">
        @if (auth()->user()->estAdmin())
            <a href="{{ route('programmes.create') }}" class="btn-primaire"><x-icone nom="plus" class="h-4 w-4" /> Nouveau programme</a>
        @endif
    </x-entete>

    <div class="space-y-5">
        @forelse ($programmes as $p)
            <div class="carte overflow-hidden {{ $p->actif ? '' : 'opacity-60' }}">
                <div class="carte-entete">
                    <div>
                        <h2><span class="text-marque-600">{{ $p->code }}</span> — {{ $p->libelle }}</h2>
                        <p class="sous-titre mt-0.5">Responsable : {{ $p->responsable ?: '—' }}@if ($p->objectif) · {{ $p->objectif }}@endif</p>
                    </div>
                    @if (auth()->user()->estAdmin())
                        <a href="{{ route('programmes.edit', $p) }}" class="btn-secondaire btn-petit">Modifier</a>
                    @endif
                </div>
                <table class="tableau">
                    <thead><tr><th class="w-24">Action</th><th>Libellé</th><th class="num">Lignes de crédits</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($p->actions as $a)
                            <tr>
                                <td class="font-medium tabular-nums">{{ $p->code }}.{{ $a->code }}</td>
                                <td>
                                    @if (auth()->user()->estAdmin())
                                        <form method="POST" action="{{ route('actions.update', $a) }}" class="flex gap-2">
                                            @csrf @method('PUT')
                                            <input name="code" value="{{ $a->code }}" class="champ-petit w-20">
                                            <input name="libelle" value="{{ $a->libelle }}" class="champ-petit">
                                            <button class="btn-secondaire btn-petit">Enregistrer</button>
                                        </form>
                                    @else
                                        {{ $a->libelle }}
                                    @endif
                                </td>
                                <td class="num">{{ $a->lignes_credit_count }}</td>
                                <td class="text-right">
                                    @if (auth()->user()->estAdmin() && $a->lignes_credit_count === 0)
                                        <form method="POST" action="{{ route('actions.destroy', $a) }}" data-confirm="Supprimer cette action ?">
                                            @csrf @method('DELETE')<button class="text-xs text-red-700 hover:underline">Supprimer</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500">Aucune action.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if (auth()->user()->estAdmin())
                    <form method="POST" action="{{ route('programmes.actions.store', $p) }}" class="flex flex-wrap items-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3">
                        @csrf
                        <div><label class="etiquette text-xs">Code action</label><input name="code" class="champ-petit w-24" placeholder="04" required></div>
                        <div class="min-w-64 flex-1"><label class="etiquette text-xs">Libellé</label><input name="libelle" class="champ-petit" required></div>
                        <button class="btn-secondaire btn-petit">+ Ajouter l’action</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="carte carte-corps text-slate-500">Aucun programme. Créez les programmes de votre ministère.</div>
        @endforelse
    </div>
</x-layout>
