<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Mandat de paiement (ordonnancement). */
class Mandat extends Model
{
    use \App\Models\Concerns\Journalise;

    protected static function booted(): void
    {
        // Utilisateur rattaché à un service : il ne voit que les données de son service.
        static::addGlobalScope(new \App\Models\Scopes\ParService('liquidation'));
    }

    protected $table = 'mandats';

    protected $fillable = ['liquidation_id', 'numero', 'date', 'montant', 'statut', 'motif_rejet', 'pris_en_charge_le', 'date_paiement', 'ecriture_id', 'user_id'];

    public const STATUTS = [
        'emis' => 'Émis (transmis au comptable)',
        'pris_en_charge' => 'Pris en charge',
        'rejete' => 'Rejeté par le comptable',
        'paye' => 'Payé',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'montant' => 'decimal:2', 'pris_en_charge_le' => 'datetime', 'date_paiement' => 'date'];
    }

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(Liquidation::class);
    }

    public function ecriture(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class);
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(MouvementTresorerie::class);
    }

    public function engagement(): ?Engagement
    {
        return $this->liquidation?->engagement;
    }
}
