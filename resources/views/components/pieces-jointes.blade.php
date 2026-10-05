@props(['objet', 'type'])
@php
    $pieces = \App\Models\PieceJointe::with('user')->where('objet_type', $objet->getMorphClass())->where('objet_id', $objet->getKey())->latest()->get();
    $u = auth()->user();
    $peutAjouter = $u->aRole('ordonnateur', 'controleur', 'comptable');
@endphp
<div class="carte" id="pieces-jointes">
    <div class="carte-entete">
        <h2>Pièces jointes <span class="font-normal text-slate-400">({{ $pieces->count() }})</span></h2>
    </div>
    @if ($pieces->isNotEmpty())
        <ul class="divide-y divide-slate-100">
            @foreach ($pieces as $p)
                <li class="flex items-start gap-3 px-6 py-3 text-sm">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $p->estImage() ? 'bg-emerald-50 text-emerald-700' : 'bg-marque-50 text-marque-700' }} text-[10px] font-bold uppercase">{{ pathinfo($p->nom, PATHINFO_EXTENSION) ?: 'doc' }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('pieces.telecharger', $p) }}" target="_blank" class="lien block truncate font-medium">{{ $p->nom }}</a>
                        <p class="text-xs text-slate-500">{{ \App\Models\PieceJointe::CATEGORIES[$p->categorie] ?? $p->categorie }} · {{ $p->tailleLisible() }} · {{ $p->user?->name ?? '—' }}, le {{ $p->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div class="no-print flex shrink-0 items-center gap-3">
                        <a href="{{ route('pieces.telecharger', ['piece' => $p, 'telecharger' => 1]) }}" class="lien text-xs">Télécharger</a>
                        @if ($u->estAdmin() || $p->user_id === $u->id)
                            <form method="POST" action="{{ route('pieces.destroy', $p) }}" data-confirm="Retirer la pièce « {{ $p->nom }} » ?">@csrf @method('DELETE')<button class="text-xs text-red-700 hover:underline">Retirer</button></form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="px-6 py-4 text-sm text-slate-500">Aucune pièce jointe. Ajoutez la facture, le PV de réception, l’ordre de mission ou l’acte signé.</p>
    @endif
    @if ($peutAjouter)
        <form method="POST" action="{{ route('pieces.store', ['type' => $type, 'id' => $objet->getKey()]) }}" enctype="multipart/form-data" class="no-print flex flex-wrap items-end gap-3 border-t border-slate-100 px-6 py-4">
            @csrf
            <div class="min-w-48 flex-1">
                <label class="etiquette" for="fichiers-{{ $type }}">Fichiers</label>
                <input id="fichiers-{{ $type }}" type="file" name="fichiers[]" multiple required accept=".{{ implode(',.', \App\Models\PieceJointe::EXTENSIONS) }}" class="champ py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-marque-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-marque-700">
            </div>
            <div>
                <label class="etiquette" for="categorie-{{ $type }}">Type de pièce</label>
                <select id="categorie-{{ $type }}" name="categorie" class="champ">
                    @foreach (\App\Models\PieceJointe::CATEGORIES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
            </div>
            <button class="btn-secondaire">Joindre</button>
            <p class="aide w-full">PDF, images, Word ou Excel · {{ \App\Models\PieceJointe::TAILLE_MAX_KO / 1024 }} Mo maximum par fichier · 10 fichiers à la fois.</p>
        </form>
    @endif
</div>
