<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Client ou fournisseur.
 */
class Tiers extends Model
{
    use \App\Models\Concerns\Journalise;

    protected $table = 'tiers';

    protected $fillable = ['type', 'code', 'nom', 'ninea', 'rib', 'adresse', 'telephone', 'email', 'compte_id', 'actif'];

    public const TYPES = [
        'fournisseur' => 'Fournisseur / bénéficiaire',
        'redevable' => 'Redevable',
    ];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }

    public function marches(): HasMany
    {
        return $this->hasMany(Marche::class);
    }

    public function titresRecette(): HasMany
    {
        return $this->hasMany(TitreRecette::class);
    }

    public function lignesEcriture(): HasMany
    {
        return $this->hasMany(LigneEcriture::class);
    }

    public function estRedevable(): bool
    {
        return $this->type === 'redevable';
    }

    /**
     * Solde comptable du tiers (débit - crédit) sur les écritures validées.
     * Positif : le tiers nous doit (client) ; négatif : nous lui devons (fournisseur).
     */
    public function soldeComptable(?Exercice $exercice = null): float
    {
        $q = $this->lignesEcriture()
            ->whereHas('ecriture', function ($e) use ($exercice) {
                $e->where('statut', 'validee');
                if ($exercice) {
                    $e->where('exercice_id', $exercice->id);
                }
            });

        return round((float) $q->sum('debit') - (float) $q->sum('credit'), 2);
    }

    /** Mandats pris en charge non encore payés (fournisseur) ou titres non recouvrés (redevable). */
    public function resteARegler(): float
    {
        if ($this->estRedevable()) {
            return round((float) $this->titresRecette()->whereIn('statut', ['emis', 'partiellement_recouvre'])
                ->selectRaw('COALESCE(SUM(montant - montant_recouvre), 0) as r')->value('r'), 2);
        }

        return round((float) Mandat::whereIn('statut', ['emis', 'pris_en_charge'])
            ->whereHas('liquidation.engagement', fn ($q) => $q->where('tiers_id', $this->id))
            ->sum('montant'), 2);
    }
}
