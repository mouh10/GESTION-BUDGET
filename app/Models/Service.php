<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Service gestionnaire de crédits (classification administrative). */
class Service extends Model
{
    protected $table = 'services';

    protected $fillable = ['code', 'libelle', 'responsable', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function lignesCredit(): HasMany
    {
        return $this->hasMany(LigneCredit::class);
    }

    public function getIntituleAttribute(): string
    {
        return $this->code.' - '.$this->libelle;
    }
}
