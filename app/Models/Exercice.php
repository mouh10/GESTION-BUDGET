<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercice extends Model
{
    use \App\Models\Concerns\Journalise;

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

        // Mémorisé pour la durée de la requête : le menu, les notifications et le contrôleur
        // l'utilisent tous, inutile de relancer la même requête plusieurs fois.
        $attributs = request()->attributes;
        $cle = 'exercice_courant.'.($id ?? 'defaut');
        if (($memo = $attributs->get($cle)) instanceof self) {
            return $memo;
        }

        $exercice = $id ? static::find($id) : null;
        $exercice ??= static::where('cloture', false)->orderByDesc('date_debut')->first();
        $exercice ??= static::orderByDesc('date_debut')->first();

        if ($exercice) {
            $attributs->set($cle, $exercice);
        }

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
