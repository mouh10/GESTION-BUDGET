<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ligne de crédits : programme/action × service × nature économique × source de financement.
 * Porte les autorisations d'engagement (AE) et crédits de paiement (CP) votés.
 */
class LigneCredit extends Model
{
    protected $table = 'lignes_credit';

    protected $fillable = ['exercice_id', 'action_id', 'service_id', 'nature_id', 'source', 'libelle', 'ae_initiale', 'cp_initial'];

    public const SOURCES = [
        'etat' => 'Ressources internes (État)',
        'emprunt' => 'Emprunt extérieur',
        'don' => 'Don extérieur',
    ];

    protected function casts(): array
    {
        return ['ae_initiale' => 'decimal:2', 'cp_initial' => 'decimal:2'];
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(Nature::class);
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }

    public function mouvementsModification(): HasMany
    {
        return $this->hasMany(ModificationLigne::class);
    }

    /** Imputation complète : programme.action / service / nature / source. */
    public function imputation(): string
    {
        $this->loadMissing('action.programme', 'service', 'nature');

        return $this->action->programme->code.'.'.$this->action->code.' / '.$this->service->code.' / '.$this->nature->code.' / '.strtoupper($this->source);
    }

    public function intitule(): string
    {
        $this->loadMissing('nature');

        return $this->imputation().' — '.($this->libelle ?: $this->nature->libelle);
    }

    public function estUtilisee(): bool
    {
        return $this->engagements()->exists() || $this->mouvementsModification()->exists();
    }
}
