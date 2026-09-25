<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrateur',
        'ordonnateur' => 'Ordonnateur / gestionnaire de crédits',
        'controleur' => 'Contrôleur financier',
        'comptable' => 'Comptable public',
        'lecteur' => 'Lecteur (consultation)',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'actif'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    public function estAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Vrai si l'utilisateur possède l'un des rôles donnés (l'administrateur les a tous). */
    public function aRole(string ...$roles): bool
    {
        return $this->actif && ($this->role === 'admin' || in_array($this->role, $roles, true));
    }

    /** Ordonnateur : prépare le budget, engage, liquide et mandate. */
    public function estOrdonnateur(): bool
    {
        return $this->aRole('ordonnateur');
    }

    /** Contrôleur financier : vise ou rejette les engagements. */
    public function estControleur(): bool
    {
        return $this->aRole('controleur');
    }

    /** Comptable public : prend en charge, paie, recouvre et tient la comptabilité. */
    public function estComptable(): bool
    {
        return $this->aRole('comptable');
    }

    public function libelleRole(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
