<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModificationLigne extends Model
{
    protected $table = 'modification_lignes';

    protected $fillable = ['modification_id', 'ligne_credit_id', 'ae', 'cp'];

    protected function casts(): array
    {
        return ['ae' => 'decimal:2', 'cp' => 'decimal:2'];
    }

    public function modification(): BelongsTo
    {
        return $this->belongsTo(Modification::class);
    }

    public function ligneCredit(): BelongsTo
    {
        return $this->belongsTo(LigneCredit::class);
    }
}
