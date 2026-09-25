<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Liquidation : constatation du service fait et arrêté du montant dû. */
class Liquidation extends Model
{
    protected $table = 'liquidations';

    protected $fillable = ['engagement_id', 'numero', 'date', 'reference_facture', 'date_service_fait', 'montant', 'observations', 'statut', 'user_id'];

    public const STATUTS = ['validee' => 'Validée', 'annulee' => 'Annulée'];

    protected function casts(): array
    {
        return ['date' => 'date', 'date_service_fait' => 'date', 'montant' => 'decimal:2'];
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function mandats(): HasMany
    {
        return $this->hasMany(Mandat::class)->orderBy('id');
    }

    /** Mandat en cours (non rejeté). */
    public function mandatActif(): ?Mandat
    {
        return $this->mandats()->where('statut', '!=', 'rejete')->first();
    }
}
