<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrevisionRecette extends Model
{
    protected $table = 'previsions_recette';

    protected $fillable = ['exercice_id', 'service_id', 'nature_id', 'libelle', 'montant_prevu'];

    protected function casts(): array
    {
        return ['montant_prevu' => 'decimal:2'];
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(Nature::class);
    }

    public function titres(): HasMany
    {
        return $this->hasMany(TitreRecette::class);
    }

    public function intitule(): string
    {
        $this->loadMissing('nature', 'service');

        return $this->nature->code.' — '.($this->libelle ?: $this->nature->libelle).($this->service ? ' ('.$this->service->code.')' : '');
    }
}
