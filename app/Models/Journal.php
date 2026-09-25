<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $table = 'journaux';

    protected $fillable = ['code', 'libelle', 'type'];

    public const TYPES = [
        'achats' => 'Achats',
        'ventes' => 'Ventes',
        'banque' => 'Banque',
        'caisse' => 'Caisse',
        'operations_diverses' => 'Opérations diverses',
    ];

    public function ecritures(): HasMany
    {
        return $this->hasMany(Ecriture::class);
    }

    public static function parCode(string $code): self
    {
        return static::where('code', $code)->firstOrFail();
    }

    public function getIntituleAttribute(): string
    {
        return $this->code.' - '.$this->libelle;
    }
}
