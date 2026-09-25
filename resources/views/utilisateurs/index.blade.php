<x-layout titre="Utilisateurs">
    <x-entete titre="Utilisateurs" sous-titre="Administrateur : tout ; Comptable : saisie et consultation ; Lecteur : consultation seule.">
        <a href="{{ route('utilisateurs.create') }}" class="btn-primaire">Nouvel utilisateur</a>
    </x-entete>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>État</th><th></th></tr></thead>
            <tbody>
                @foreach ($utilisateurs as $u)
                    <tr>
                        <td class="font-medium">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->libelleRole() }}</td>
                        <td><span class="{{ $u->actif ? 'badge-vert' : 'badge-gris' }}">{{ $u->actif ? 'Actif' : 'Désactivé' }}</span></td>
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
