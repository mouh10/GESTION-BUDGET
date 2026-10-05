<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Marché public ou contrat. */
class Marche extends Model
{
    use \App\Models\Concerns\Journalise;

    protected static function booted(): void
    {
        // Utilisateur rattaché à un service : il ne voit que les données de son service.
        static::addGlobalScope(new \App\Models\Scopes\ParService('ligne'));
    }

    protected $table = 'marches';

    protected $fillable = ['exercice_id', 'numero', 'objet', 'tiers_id', 'type', 'mode_passation', 'montant', 'date_signature', 'date_fin', 'statut', 'ligne_credit_id'];

    public const TYPES = [
        'travaux' => 'Travaux',
        'fournitures' => 'Fournitures',
        'services' => 'Services courants',
        'prestations_intellectuelles' => 'Prestations intellectuelles',
    ];

    public const MODES = [
        'aoo' => "Appel d'offres ouvert",
        'aor' => "Appel d'offres restreint",
        'drp' => 'Demande de renseignements et de prix (DRP)',
        'entente_directe' => 'Entente directe',
        'consultation' => 'Consultation / demande de cotation',
    ];

    public const STATUTS = ['en_cours' => 'En cours', 'termine' => 'Terminé', 'resilie' => 'Résilié'];

    protected function casts(): array
    {
        return ['montant' => 'decimal:2', 'date_signature' => 'date', 'date_fin' => 'date'];
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function ligneCredit(): BelongsTo
    {
        return $this->belongsTo(LigneCredit::class);
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }

    /** Montant déjà engagé sur le marché (engagements soumis ou visés). */
    public function montantEngage(?int $sauf = null): float
    {
        return round((float) $this->engagements()->whereIn('statut', ['soumis', 'vise'])
            ->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))->sum('montant'), 2);
    }

    public function resteAEngager(): float
    {
        return round((float) $this->montant - $this->montantEngage(), 2);
    }
}
