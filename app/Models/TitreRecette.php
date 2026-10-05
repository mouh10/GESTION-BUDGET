<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Titre (ordre) de recette émis à l'encontre d'un redevable. */
class TitreRecette extends Model
{
    use \App\Models\Concerns\Journalise;

    protected static function booted(): void
    {
        // Utilisateur rattaché à un service : il ne voit que les données de son service.
        static::addGlobalScope(new \App\Models\Scopes\ParService('prevision'));
    }

    protected $table = 'titres_recette';

    protected $fillable = ['exercice_id', 'prevision_recette_id', 'tiers_id', 'numero', 'date', 'date_echeance', 'objet', 'montant', 'montant_recouvre', 'statut', 'ecriture_id', 'user_id'];

    public const STATUTS = [
        'emis' => 'Émis',
        'partiellement_recouvre' => 'Partiellement recouvré',
        'recouvre' => 'Recouvré',
        'annule' => 'Annulé',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'date_echeance' => 'date', 'montant' => 'decimal:2', 'montant_recouvre' => 'decimal:2'];
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function prevision(): BelongsTo
    {
        return $this->belongsTo(PrevisionRecette::class, 'prevision_recette_id');
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function ecriture(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class);
    }

    public function recouvrements(): HasMany
    {
        return $this->hasMany(MouvementTresorerie::class)->orderBy('date');
    }

    public function reste(): float
    {
        return round((float) $this->montant - (float) $this->montant_recouvre, 2);
    }

    public function peutEtreRecouvre(): bool
    {
        return in_array($this->statut, ['emis', 'partiellement_recouvre'], true);
    }
}
