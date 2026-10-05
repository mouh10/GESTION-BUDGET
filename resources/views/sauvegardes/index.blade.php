<x-layout titre="Sauvegardes">
    <x-entete titre="Sauvegardes" :sous-titre="'Base de données et pièces jointes, réunies dans une archive ZIP. Conservation : '.config('gestion.sauvegardes.conserver_jours').' jours.'">
        <form method="POST" action="{{ route('sauvegardes.store') }}">@csrf<button class="btn-primaire">Sauvegarder maintenant</button></form>
    </x-entete>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="carte overflow-x-auto lg:col-span-2">
            <table class="tableau">
                <thead><tr><th>Archive</th><th>Date</th><th class="num">Taille</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sauvegardes as $s)
                        <tr>
                            <td class="font-medium">{{ $s['nom'] }}</td>
                            <td class="whitespace-nowrap">{{ $s['date']->format('d/m/Y à H:i') }}</td>
                            <td class="num">{{ $service->tailleLisible($s['taille']) }}</td>
                            <td class="text-right"><a href="{{ route('sauvegardes.telecharger', $s['nom']) }}" class="lien text-xs">Télécharger</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-slate-500">Aucune sauvegarde pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="carte carte-corps space-y-3 text-sm text-slate-600">
            <h2>Sauvegarde automatique</h2>
            <p>Une sauvegarde est lancée <strong>chaque nuit à {{ config('gestion.sauvegardes.heure') }}</strong>, à condition que le planificateur de Laravel tourne sur le serveur :</p>
            <p><span class="font-medium text-slate-800">Linux (cron)</span><br><code class="text-xs">* * * * * cd {{ base_path() }} && php artisan schedule:run</code></p>
            <p><span class="font-medium text-slate-800">Windows</span> : Planificateur de tâches, toutes les minutes, action <code class="text-xs">php artisan schedule:run</code> dans le dossier de l’application.</p>
            <p>Commande manuelle : <code class="text-xs">php artisan gestion:sauvegarder</code></p>
            <p class="text-xs text-slate-500">Dossier : {{ $dossier }}<br>Copiez régulièrement ces archives sur un autre support (disque externe, serveur distant).</p>
        </div>
    </div>
</x-layout>
