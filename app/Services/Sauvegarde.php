<?php

namespace App\Services;

use App\Exceptions\GestionException;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Sauvegarde complète : copie de la base de données (PostgreSQL, MySQL ou SQLite)
 * et des pièces jointes, réunies dans une archive ZIP datée.
 * Les archives plus anciennes que la durée de conservation sont supprimées.
 */
class Sauvegarde
{
    public function dossier(): string
    {
        $d = config('gestion.sauvegardes.dossier') ?: storage_path('app/sauvegardes');
        File::ensureDirectoryExists($d);

        return rtrim($d, '/\\');
    }

    /** @return array{fichier:string, taille:int, supprimees:int} */
    public function lancer(): array
    {
        $horodatage = now()->format('Y-m-d_His');
        $temp = storage_path('app/sauvegarde-'.$horodatage);
        File::ensureDirectoryExists($temp);

        try {
            $base = $this->exporterBase($temp);

            $archive = $this->dossier().'/gestion_'.$horodatage.'.zip';
            $zip = new \ZipArchive();
            if ($zip->open($archive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new GestionException("Impossible de créer l'archive de sauvegarde dans {$this->dossier()}.");
            }
            $zip->addFile($base, basename($base));

            $pieces = Storage::disk('local')->path('pieces');
            if (is_dir($pieces)) {
                foreach (File::allFiles($pieces) as $f) {
                    $zip->addFile($f->getPathname(), 'pieces/'.str_replace('\\', '/', $f->getRelativePathname()));
                }
            }
            $zip->setArchiveComment('Gestion — sauvegarde du '.now()->format('d/m/Y H:i').' — base '.config('database.default'));
            $zip->close();
        } finally {
            File::deleteDirectory($temp);
        }

        $supprimees = $this->purger();
        $taille = filesize($archive);
        Audit::enregistrer('sauvegarde', null, 'Sauvegarde '.basename($archive).' ('.$this->tailleLisible($taille).')');

        return ['fichier' => $archive, 'taille' => $taille, 'supprimees' => $supprimees];
    }

    /** Liste des sauvegardes, de la plus récente à la plus ancienne. */
    public function lister(): array
    {
        return collect(File::glob($this->dossier().'/gestion_*.zip'))
            ->map(fn ($f) => ['nom' => basename($f), 'chemin' => $f, 'taille' => filesize($f), 'date' => \Illuminate\Support\Carbon::createFromTimestamp(filemtime($f))])
            ->sortByDesc('date')->values()->all();
    }

    public function chemin(string $nom): ?string
    {
        $chemin = $this->dossier().'/'.basename($nom);

        return preg_match('/^gestion_[0-9_-]+\.zip$/', basename($nom)) && is_file($chemin) ? $chemin : null;
    }

    /** Supprime les sauvegardes plus anciennes que la durée de conservation (en jours). */
    public function purger(): int
    {
        $jours = (int) config('gestion.sauvegardes.conserver_jours', 30);
        $n = 0;
        foreach ($this->lister() as $s) {
            if ($s['date']->lt(now()->subDays($jours))) {
                File::delete($s['chemin']);
                $n++;
            }
        }

        return $n;
    }

    public function tailleLisible(int $t): string
    {
        return $t >= 1048576 ? number_format($t / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($t / 1024)).' Ko';
    }

    protected function exporterBase(string $temp): string
    {
        $nom = config('database.default');
        $c = config("database.connections.$nom");

        return match ($c['driver']) {
            'sqlite' => $this->sqlite($c, $temp),
            'pgsql' => $this->pgsql($c, $temp),
            'mysql', 'mariadb' => $this->mysql($c, $temp),
            default => throw new GestionException("Sauvegarde non prise en charge pour le pilote {$c['driver']}."),
        };
    }

    protected function sqlite(array $c, string $temp): string
    {
        $cible = $temp.'/base.sqlite';
        if (($c['database'] ?? '') === ':memory:') {
            throw new GestionException('Base SQLite en mémoire : rien à sauvegarder.');
        }
        // VACUUM INTO produit une copie cohérente même si la base est en cours d'utilisation.
        DB::statement('VACUUM INTO ?', [$cible]);

        return $cible;
    }

    protected function pgsql(array $c, string $temp): string
    {
        $cible = $temp.'/base.sql';
        $r = Process::env(['PGPASSWORD' => (string) ($c['password'] ?? '')])->timeout(600)->run([
            config('gestion.sauvegardes.pg_dump', 'pg_dump'),
            '--host='.($c['host'] ?? '127.0.0.1'), '--port='.($c['port'] ?? 5432), '--username='.($c['username'] ?? 'postgres'),
            '--no-owner', '--no-privileges', '--encoding=UTF8', '--file='.$cible, (string) $c['database'],
        ]);
        if (! $r->successful()) {
            throw new GestionException('pg_dump a échoué : '.trim($r->errorOutput() ?: $r->output()).' — vérifiez GESTION_PG_DUMP dans le fichier .env.');
        }

        return $cible;
    }

    protected function mysql(array $c, string $temp): string
    {
        $cible = $temp.'/base.sql';
        $r = Process::env(['MYSQL_PWD' => (string) ($c['password'] ?? '')])->timeout(600)->run([
            config('gestion.sauvegardes.mysqldump', 'mysqldump'),
            '--host='.($c['host'] ?? '127.0.0.1'), '--port='.($c['port'] ?? 3306), '--user='.($c['username'] ?? 'root'),
            '--single-transaction', '--routines', '--result-file='.$cible, (string) $c['database'],
        ]);
        if (! $r->successful()) {
            throw new GestionException('mysqldump a échoué : '.trim($r->errorOutput() ?: $r->output()));
        }

        return $cible;
    }
}
