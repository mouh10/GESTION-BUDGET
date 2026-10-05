<?php

namespace App\Support;

use App\Models\JournalAudit;
use Illuminate\Database\Eloquent\Model;

/**
 * Enregistre les actions dans le journal d'audit (qui, quoi, quand, depuis où).
 * Le journal n'est jamais modifié ni purgé par l'application.
 */
class Audit
{
    protected static bool $actif = true;

    public static function desactiver(): void
    {
        static::$actif = false;
    }

    public static function activer(): void
    {
        static::$actif = true;
    }

    public static function estActif(): bool
    {
        return static::$actif;
    }

    /** Exécute un traitement sans journaliser (chargement des données de démonstration…). */
    public static function sans(callable $traitement): mixed
    {
        $avant = static::$actif;
        static::$actif = false;
        try {
            return $traitement();
        } finally {
            static::$actif = $avant;
        }
    }

    public static function enregistrer(string $action, ?Model $sujet = null, ?string $description = null, ?array $details = null, ?int $userId = null): void
    {
        if (! static::$actif) {
            return;
        }

        $requete = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        JournalAudit::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'sujet_type' => $sujet?->getMorphClass(),
            'sujet_id' => $sujet?->getKey(),
            'sujet_libelle' => $sujet ? static::libelle($sujet) : null,
            'description' => $description ? mb_substr($description, 0, 500) : null,
            'details' => $details ?: null,
            'ip' => $requete?->ip(),
        ]);
    }

    /** « Engagement EJ-2026-0001 », « Fournisseur Papeterie Moderne »… */
    public static function libelle(Model $m): string
    {
        if (method_exists($m, 'libelleAudit')) {
            return $m->libelleAudit();
        }
        $nom = JournalAudit::TYPES[$m->getMorphClass()] ?? class_basename($m);
        $id = $m->numero ?? $m->numero_piece ?? $m->code ?? $m->nom ?? $m->name ?? $m->libelle ?? ('n° '.$m->getKey());

        return trim($nom.' '.$id);
    }
}
