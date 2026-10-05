<?php

namespace App\Models\Concerns;

use App\Support\Audit;

/**
 * Journalise automatiquement la création, les modifications (avec l'ancienne et la nouvelle
 * valeur de chaque champ), les changements de statut et la suppression d'un modèle.
 */
trait Journalise
{
    /** Champs jamais recopiés dans le journal. */
    protected static array $champsNonJournalises = ['updated_at', 'created_at', 'password', 'remember_token'];

    public static function bootJournalise(): void
    {
        static::created(function ($m) {
            Audit::enregistrer('creation', $m, 'Création', ['valeurs' => static::valeursJournalisables($m->getAttributes())]);
        });

        static::updated(function ($m) {
            $changements = [];
            foreach ($m->getChanges() as $champ => $nouveau) {
                if (in_array($champ, static::$champsNonJournalises, true)) {
                    continue;
                }
                $changements[$champ] = ['avant' => $m->getOriginal($champ) instanceof \DateTimeInterface ? $m->getOriginal($champ)->format('Y-m-d H:i') : $m->getRawOriginal($champ), 'apres' => $nouveau];
            }
            if (array_key_exists('password', $m->getChanges())) {
                $changements['mot_de_passe'] = ['avant' => '•••', 'apres' => 'modifié'];
            }
            if (! $changements) {
                return;
            }

            if (isset($changements['statut'])) {
                $statuts = defined(static::class.'::STATUTS') ? static::STATUTS : [];
                $avant = $statuts[$changements['statut']['avant']] ?? $changements['statut']['avant'];
                $apres = $statuts[$changements['statut']['apres']] ?? $changements['statut']['apres'];
                Audit::enregistrer('statut', $m, "Statut : {$avant} → {$apres}", ['changements' => $changements]);

                return;
            }

            Audit::enregistrer('modification', $m, 'Modification de '.implode(', ', array_keys($changements)), ['changements' => $changements]);
        });

        static::deleted(function ($m) {
            Audit::enregistrer('suppression', $m, 'Suppression', ['valeurs' => static::valeursJournalisables($m->getAttributes())]);
        });
    }

    protected static function valeursJournalisables(array $attributs): array
    {
        return array_diff_key($attributs, array_flip(array_merge(static::$champsNonJournalises, ['id'])));
    }
}
