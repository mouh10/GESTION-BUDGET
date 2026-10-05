<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Document numérisé rattaché à un engagement, un mandat, un titre, un acte ou un marché. */
class PieceJointe extends Model
{
    protected $table = 'pieces_jointes';

    protected $fillable = ['objet_type', 'objet_id', 'categorie', 'nom', 'chemin', 'mime', 'taille', 'user_id'];

    public const CATEGORIES = [
        'facture' => 'Facture / mémoire',
        'pv_reception' => 'PV de réception / attestation de service fait',
        'bon_commande' => 'Bon de commande / contrat',
        'ordre_mission' => 'Ordre de mission',
        'acte' => 'Acte, décision, arrêté',
        'justificatif' => 'Autre justificatif',
        'autre' => 'Autre',
    ];

    /** Objets qui acceptent des pièces jointes : clé d'URL => classe. */
    public const OBJETS = [
        'engagement' => Engagement::class,
        'mandat' => Mandat::class,
        'titre' => TitreRecette::class,
        'modification' => Modification::class,
        'marche' => Marche::class,
    ];

    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods'];

    public const TAILLE_MAX_KO = 10240;

    public function objet(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tailleLisible(): string
    {
        $t = (int) $this->taille;

        return $t >= 1048576 ? number_format($t / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($t / 1024)).' Ko';
    }

    public function estImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
