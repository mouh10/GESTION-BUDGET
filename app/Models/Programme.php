<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/** Programme budgétaire (budget-programme). */
class Programme extends Model
{
    protected $table = 'programmes';

    protected $fillable = ['code', 'libelle', 'responsable', 'objectif', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class)->orderBy('code');
    }

    public function lignesCredit(): HasManyThrough
    {
        return $this->hasManyThrough(LigneCredit::class, Action::class);
    }

    public function getIntituleAttribute(): string
    {
        return $this->code.' - '.$this->libelle;
    }
}
