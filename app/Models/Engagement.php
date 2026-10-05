<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Engagement juridique de la dépense (bon d'engagement). */
class Engagement extends Model
{
    use \App\Models\Concerns\Journalise;

    protected static function booted(): void
    {
        // Utilisateur rattaché à un service : il ne voit que les données de son service.
        static::addGlobalScope(new \App\Models\Scopes\ParService('ligne'));
    }

    protected $table = 'engagements';

    protected $fillable = [
        'exercice_id', 'numero', 'date', 'ligne_credit_id', 'tiers_id', 'marche_id', 'type', 'objet', 'montant',
        'statut', 'motif_rejet', 'soumis_le', 'vise_le', 'viseur_id', 'user_id',
    ];

    public const TYPES = [
        'bon_commande' => 'Bon de commande',
        'marche' => 'Marché / contrat',
        'decision' => 'Décision / arrêté',
        'salaires' => 'État de salaires',
        'mission' => 'Ordre de mission',
        'autre' => 'Autre',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'soumis' => 'Soumis au visa',
        'vise' => 'Visé',
        'rejete' => 'Rejeté',
        'annule' => 'Annulé',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'montant' => 'decimal:2', 'soumis_le' => 'datetime', 'vise_le' => 'datetime'];
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function ligneCredit(): BelongsTo
    {
        return $this->belongsTo(LigneCredit::class);
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function marche(): BelongsTo
    {
        return $this->belongsTo(Marche::class);
    }

    public function viseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viseur_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function liquidations(): HasMany
    {
        return $this->hasMany(Liquidation::class)->orderBy('date')->orderBy('id');
    }

    public function estModifiable(): bool
    {
        return in_array($this->statut, ['brouillon', 'rejete'], true);
    }

    public function montantLiquide(): float
    {
        return round((float) $this->liquidations()->where('statut', 'validee')->sum('montant'), 2);
    }

    public function resteALiquider(): float
    {
        return round((float) $this->montant - $this->montantLiquide(), 2);
    }

    public function montantPaye(): float
    {
        return round((float) Mandat::where('statut', 'paye')
            ->whereHas('liquidation', fn ($q) => $q->where('engagement_id', $this->id))->sum('montant'), 2);
    }
}
