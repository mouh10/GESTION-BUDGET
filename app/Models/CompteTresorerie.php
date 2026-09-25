<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compte bancaire, caisse ou portefeuille de mobile money.
 */
class CompteTresorerie extends Model
{
    protected $table = 'comptes_tresorerie';

    protected $fillable = ['nom', 'type', 'numero', 'compte_id', 'journal_id', 'solde_initial', 'actif'];

    public const TYPES = [
        'tresor' => 'Compte au Trésor',
        'banque' => 'Banque',
        'regie' => "Régie d'avances / de recettes",
        'caisse' => 'Caisse',
    ];

    protected function casts(): array
    {
        return ['solde_initial' => 'decimal:2', 'actif' => 'boolean'];
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementTresorerie::class);
    }

    /** Solde à une date donnée (incluse), ou solde actuel. */
    public function solde($auDate = null): float
    {
        $q = $this->mouvements();
        if ($auDate) {
            $q->whereDate('date', '<=', $auDate);
        }

        $entrees = (clone $q)->where('type', 'encaissement')->sum('montant');
        $sorties = (clone $q)->where('type', 'decaissement')->sum('montant');

        return round((float) $this->solde_initial + (float) $entrees - (float) $sorties, 2);
    }
}
