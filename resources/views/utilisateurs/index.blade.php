<x-layout titre="Utilisateurs">
    <x-entete titre="Utilisateurs" sous-titre="Administrateur : tout ; Ordonnateur : engagements, mandats, titres ; Contrôleur financier : visas ; Comptable public : paiements, trésorerie, comptabilité ; Lecteur : consultation.">
        <a href="{{ route('utilisateurs.create') }}" class="btn-primaire">Nouvel utilisateur</a>
    </x-entete>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Périmètre</th><th>Dernière connexion</th><th>État</th><th></th></tr></thead>
            <tbody>
                @foreach ($utilisateurs as $u)
                    <tr>
                        <td class="font-medium">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->libelleRole() }}</td>
                        <td>{{ $u->service && ! $u->estAdmin() ? $u->service->code : 'Tous les services' }}</td>
                        <td class="whitespace-nowrap text-slate-500">{{ $u->derniere_connexion?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                        <td><span class="{{ $u->actif ? 'badge-vert' : 'badge-gris' }}">{{ $u->actif ? 'Actif' : 'Désactivé' }}</span>@if ($u->doit_changer_mdp) <span class="badge-orange">Mot de passe provisoire</span>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('utilisateurs.edit', $u) }}" class="lien text-xs">Modifier</a>
                            @unless ($u->is(auth()->user()))
                                <form method="POST" action="{{ route('utilisateurs.destroy', $u) }}" class="ml-3 inline" data-confirm="Supprimer {{ $u->name }} ?">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-700 hover:underline">Supprimer</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
