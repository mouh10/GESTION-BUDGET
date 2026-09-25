<x-layout titre="Trésorerie">
    <x-entete titre="Banques et caisses" :sous-titre="'Trésorerie disponible : '.fcfa($total)">
        @if (auth()->user()->estComptable())
            <a href="{{ route('tresorerie.virement') }}" class="btn-secondaire">Virement interne</a>
            <a href="{{ route('tresorerie.create') }}" class="btn-primaire">Nouveau compte</a>
        @endif
    </x-entete>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($comptes as $c)
            <a href="{{ route('tresorerie.show', $c->compte) }}" class="kpi block transition hover:border-marque-500 {{ $c->compte->actif ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between">
                    <p class="font-medium text-slate-900">{{ $c->compte->nom }}</p>
                    <span class="badge-gris">{{ \App\Models\CompteTresorerie::TYPES[$c->compte->type] }}</span>
                </div>
                <p class="kpi-valeur {{ $c->solde < 0 ? 'text-red-700' : '' }}">{{ fcfa($c->solde) }}</p>
                <p class="mt-1 text-xs text-slate-500">Compte {{ $c->compte->compte->numero }} · journal {{ $c->compte->journal->code }}{{ $c->compte->numero ? ' · '.$c->compte->numero : '' }}</p>
            </a>
        @empty
            <div class="carte carte-corps text-slate-500 sm:col-span-3">Aucun compte de trésorerie. Créez votre banque ou votre caisse pour commencer.</div>
        @endforelse
    </div>

    <div class="carte overflow-x-auto">
        <div class="carte-entete"><h2>Derniers mouvements</h2></div>
        <table class="tableau">
            <thead><tr><th>Date</th><th>Compte</th><th>Libellé</th><th>Contrepartie</th><th class="num">Entrée</th><th class="num">Sortie</th></tr></thead>
            <tbody>
                @forelse ($derniers as $m)
                    <tr>
                        <td class="whitespace-nowrap">{{ date_fr($m->date) }}</td>
                        <td>{{ $m->compteTresorerie->nom }}</td>
                        <td>{{ $m->libelle }}</td>
                        <td class="text-slate-500">{{ $m->tiers?->nom ?? $m->compte?->numero.' '.$m->compte?->libelle }}</td>
                        <td class="num text-emerald-700">{{ $m->estEncaissement() ? montant($m->montant) : '' }}</td>
                        <td class="num text-red-700">{{ $m->estEncaissement() ? '' : montant($m->montant) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
