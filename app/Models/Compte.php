<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compte du plan comptable SYSCOHADA.
 */
class Compte extends Model
{
    use \App\Models\Concerns\Journalise;

    protected $table = 'comptes';

    protected $fillable = ['numero', 'libelle', 'classe', 'actif'];

    public const CLASSES = [
        1 => 'Comptes de ressources durables',
        2 => "Comptes d'actif immobilisé",
        3 => 'Comptes de stocks',
        4 => 'Comptes de tiers',
        5 => 'Comptes de trésorerie',
        6 => 'Comptes de charges des activités ordinaires',
        7 => 'Comptes de produits des activités ordinaires',
        8 => 'Comptes des autres charges et des autres produits',
        9 => 'Comptes des engagements hors bilan et analytiques',
    ];

    /** Sous-classes de la classe 8 qui sont des produits. */
    public const PRODUITS_CLASSE_8 = ['82', '84', '86', '88'];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'classe' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Compte $compte) {
            $compte->numero = trim($compte->numero);
            $compte->classe = (int) substr($compte->numero, 0, 1);
        });
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneEcriture::class);
    }

    public function scopeActifs(Builder $q): Builder
    {
        return $q->where('actif', true);
    }

    /** Comptes utilisables en saisie (au moins 3 chiffres). */
    public function scopeSaisissables(Builder $q): Builder
    {
        return $q->actifs()->whereRaw('LENGTH(numero) >= 3')->orderBy('numero');
    }

    public function scopeClasse(Builder $q, int|array $classes): Builder
    {
        return $q->whereIn('classe', (array) $classes);
    }

    public static function parNumero(string $numero): ?self
    {
        return static::where('numero', $numero)->first();
    }

    /** Vrai pour les comptes de produits (classe 7 et 82/84/86/88). */
    public static function estProduit(string $numero): bool
    {
        return str_starts_with($numero, '7') || in_array(substr($numero, 0, 2), self::PRODUITS_CLASSE_8, true);
    }

    public static function estCharge(string $numero): bool
    {
        return str_starts_with($numero, '6') || (str_starts_with($numero, '8') && ! self::estProduit($numero));
    }

    public function getIntituleAttribute(): string
    {
        return $this->numero.' - '.$this->libelle;
    }

    public function estUtilise(): bool
    {
        return $this->lignes()->exists()
            || Nature::where('compte_id', $this->id)->exists()
            || Tiers::where('compte_id', $this->id)->exists()
            || CompteTresorerie::where('compte_id', $this->id)->exists();
    }
}
