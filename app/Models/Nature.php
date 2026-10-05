<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Classification économique : nature de dépense (par titre) ou de recette (par catégorie),
 * avec son imputation comptable.
 */
class Nature extends Model
{
    use \App\Models\Concerns\Journalise;

    protected $table = 'natures';

    protected $fillable = ['type', 'code', 'libelle', 'titre', 'compte_id', 'actif'];

    public const TYPES = ['depense' => 'Dépense', 'recette' => 'Recette'];

    /** Titres de la classification économique des dépenses (budget-programme). */
    public const TITRES_DEPENSE = [
        1 => 'Charges financières de la dette',
        2 => 'Dépenses de personnel',
        3 => "Dépenses d'acquisition de biens et services",
        4 => 'Dépenses de transfert courant',
        5 => "Dépenses d'investissement exécutées par l'État",
        6 => 'Dépenses de transfert en capital',
    ];

    /** Catégories de recettes. */
    public const CATEGORIES_RECETTE = [
        1 => 'Recettes fiscales',
        2 => 'Recettes non fiscales',
        3 => 'Dons et legs',
        4 => 'Emprunts et autres ressources de trésorerie',
    ];

    /** Titres pour lesquels AE et CP peuvent différer (investissement, transferts en capital). */
    public const TITRES_AE_DISTINCTES = [5, 6];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'titre' => 'integer'];
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function scopeDepenses(Builder $q): Builder
    {
        return $q->where('type', 'depense');
    }

    public function scopeRecettes(Builder $q): Builder
    {
        return $q->where('type', 'recette');
    }

    public function libelleTitre(): string
    {
        $liste = $this->type === 'recette' ? self::CATEGORIES_RECETTE : self::TITRES_DEPENSE;

        return $liste[$this->titre] ?? (string) $this->titre;
    }

    public function getIntituleAttribute(): string
    {
        return $this->code.' - '.$this->libelle;
    }
}
