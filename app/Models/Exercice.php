<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercice extends Model
{
    protected $table = 'exercices';

    protected $fillable = ['libelle', 'date_debut', 'date_fin', 'cloture'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'cloture' => 'boolean',
        ];
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(Ecriture::class);
    }

    public function lignesCredit(): HasMany
    {
        return $this->hasMany(LigneCredit::class);
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }

    /**
     * Exercice choisi par l'utilisateur (session), sinon le plus récent non clôturé,
     * sinon le plus récent.
     */
    public static function courant(): ?self
    {
        $id = session('exercice_id');

        $exercice = $id ? static::find($id) : null;
        $exercice ??= static::where('cloture', false)->orderByDesc('date_debut')->first();
        $exercice ??= static::orderByDesc('date_debut')->first();

        return $exercice;
    }

    /** Exercice qui contient la date donnée. */
    public static function pourDate($date): ?self
    {
        $date = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return static::whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date)
            ->orderByDesc('date_debut')
            ->first();
    }

    public function contient($date): bool
    {
        $d = \Illuminate\Support\Carbon::parse($date)->startOfDay();

        return $d->betweenIncluded($this->date_debut->copy()->startOfDay(), $this->date_fin->copy()->startOfDay());
    }

    public function annee(): string
    {
        return $this->date_debut->format('Y');
    }
}
