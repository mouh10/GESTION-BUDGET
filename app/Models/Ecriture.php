<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Écriture comptable (pièce) en partie double.
 */
class Ecriture extends Model
{
    protected $table = 'ecritures';

    protected $fillable = [
        'exercice_id', 'journal_id', 'numero_piece', 'date', 'libelle', 'reference',
        'statut', 'source_type', 'source_id', 'user_id',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'validee' => 'Validée',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneEcriture::class)->orderBy('id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function estValidee(): bool
    {
        return $this->statut === 'validee';
    }

    /** Écriture générée automatiquement (mandat, titre de recette, trésorerie, clôture). */
    public function estAutomatique(): bool
    {
        return $this->source_type !== null;
    }

    public function totalDebit(): float
    {
        return round((float) $this->lignes->sum('debit'), 2);
    }

    public function totalCredit(): float
    {
        return round((float) $this->lignes->sum('credit'), 2);
    }
}
