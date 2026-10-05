<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entrée du journal d'audit (lecture seule). */
class JournalAudit extends Model
{
    protected $table = 'journal_audit';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'sujet_type', 'sujet_id', 'sujet_libelle', 'description', 'details', 'ip'];

    public const ACTIONS = [
        'creation' => 'Création',
        'modification' => 'Modification',
        'statut' => 'Changement de statut',
        'suppression' => 'Suppression',
        'piece_jointe' => 'Pièce jointe',
        'connexion' => 'Connexion',
        'echec_connexion' => 'Échec de connexion',
        'deconnexion' => 'Déconnexion',
        'mot_de_passe' => 'Mot de passe',
        'sauvegarde' => 'Sauvegarde',
    ];

    /** Libellés des types d'objets (clés de la « morph map »). */
    public const TYPES = [
        'engagement' => 'Engagement',
        'liquidation' => 'Liquidation',
        'mandat' => 'Mandat',
        'titre' => 'Titre de recette',
        'modification' => 'Acte de modification',
        'ligne_credit' => 'Ligne de crédits',
        'prevision' => 'Prévision de recette',
        'marche' => 'Marché',
        'tiers' => 'Tiers',
        'tresorerie' => 'Compte de trésorerie',
        'mouvement' => 'Mouvement de trésorerie',
        'ecriture' => 'Écriture',
        'utilisateur' => 'Utilisateur',
        'exercice' => 'Exercice',
        'programme' => 'Programme',
        'action' => 'Action',
        'service' => 'Service',
        'nature' => 'Nature',
        'compte' => 'Compte',
        'piece' => 'Pièce jointe',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    /** Dernières actions sur un ou plusieurs objets (ex. un engagement, ses liquidations et ses mandats). */
    public static function pour(iterable $objets, int $limite = 40): \Illuminate\Support\Collection
    {
        $paires = collect($objets)->filter()->map(fn ($o) => [$o->getMorphClass(), $o->getKey()]);
        if ($paires->isEmpty()) {
            return collect();
        }

        return static::with('user')
            ->where(function ($q) use ($paires) {
                foreach ($paires->groupBy(0) as $type => $liste) {
                    $q->orWhere(fn ($w) => $w->where('sujet_type', $type)->whereIn('sujet_id', $liste->pluck(1)));
                }
            })
            ->orderByDesc('created_at')->orderByDesc('id')->limit($limite)->get();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function libelleAction(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->sujet_type] ?? (string) $this->sujet_type;
    }

    /** Lien vers l'objet concerné, s'il a une fiche. */
    public function url(): ?string
    {
        if (! $this->sujet_id) {
            return null;
        }

        return match ($this->sujet_type) {
            'engagement' => route('engagements.show', $this->sujet_id),
            'mandat' => route('mandats.show', $this->sujet_id),
            'titre' => route('titres.show', $this->sujet_id),
            'modification' => route('modifications.show', $this->sujet_id),
            'ligne_credit' => route('credits.show', $this->sujet_id),
            'marche' => route('marches.show', $this->sujet_id),
            'tiers' => route('tiers.show', $this->sujet_id),
            'tresorerie' => route('tresorerie.show', $this->sujet_id),
            'ecriture' => route('ecritures.show', $this->sujet_id),
            default => null,
        };
    }
}
