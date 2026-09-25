<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Acte de modification des crédits (arrêté, décret, loi de finances rectificative). */
class Modification extends Model
{
    protected $table = 'modifications';

    protected $fillable = ['exercice_id', 'numero', 'date', 'type', 'reference_acte', 'motif', 'statut', 'approuve_le', 'user_id'];

    public const TYPES = [
        'virement' => 'Virement de crédits (au sein d’un programme)',
        'transfert' => 'Transfert de crédits (entre programmes)',
        'ouverture' => 'Ouverture de crédits (LFR, fonds de concours…)',
        'annulation' => 'Annulation de crédits',
        'gel' => 'Mise en réserve (gel)',
        'degel' => 'Levée de réserve (dégel)',
    ];

    public const TYPES_COURTS = [
        'virement' => 'Virement', 'transfert' => 'Transfert', 'ouverture' => 'Ouverture',
        'annulation' => 'Annulation', 'gel' => 'Gel', 'degel' => 'Dégel',
    ];

    public const STATUTS = ['brouillon' => 'Brouillon', 'approuve' => 'Approuvé'];

    protected function casts(): array
    {
        return ['date' => 'date', 'approuve_le' => 'datetime'];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(ModificationLigne::class)->orderBy('id');
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estApprouvee(): bool
    {
        return $this->statut === 'approuve';
    }

    /** Les mouvements de gel/dégel n'affectent pas la dotation mais la réserve. */
    public function estReserve(): bool
    {
        return in_array($this->type, ['gel', 'degel'], true);
    }
}
