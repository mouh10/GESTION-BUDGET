<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementTresorerie extends Model
{
    use \App\Models\Concerns\Journalise;

    protected $table = 'mouvements_tresorerie';

    protected $fillable = [
        'compte_tresorerie_id', 'date', 'type', 'montant', 'libelle', 'mode', 'reference',
        'compte_id', 'tiers_id', 'mandat_id', 'titre_recette_id', 'ecriture_id', 'virement_id', 'user_id',
    ];

    public const TYPES = [
        'encaissement' => 'Encaissement',
        'decaissement' => 'Décaissement',
    ];

    public const MODES = [
        'especes' => 'Espèces',
        'virement' => 'Virement',
        'cheque' => 'Chèque',
        'mobile_money' => 'Mobile money',
        'carte' => 'Carte bancaire',
        'autre' => 'Autre',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'montant' => 'decimal:2'];
    }

    public function compteTresorerie(): BelongsTo
    {
        return $this->belongsTo(CompteTresorerie::class);
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function mandat(): BelongsTo
    {
        return $this->belongsTo(Mandat::class);
    }

    public function titreRecette(): BelongsTo
    {
        return $this->belongsTo(TitreRecette::class);
    }

    public function ecriture(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class);
    }

    public function estEncaissement(): bool
    {
        return $this->type === 'encaissement';
    }
}
