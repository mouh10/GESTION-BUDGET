<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Action d'un programme. */
class Action extends Model
{
    protected $table = 'actions';

    protected $fillable = ['programme_id', 'code', 'libelle'];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
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
