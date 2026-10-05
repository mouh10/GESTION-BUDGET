<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restreint les données budgétaires au service de l'utilisateur connecté
 * (utilisateurs rattachés à un service ; l'administrateur voit tout).
 *
 * $chemin indique comment rejoindre le service :
 *  - service     : colonne service_id de la table
 *  - ligne       : colonne ligne_credit_id
 *  - engagement  : colonne engagement_id
 *  - liquidation : colonne liquidation_id
 *  - prevision   : colonne prevision_recette_id
 *  - modification: lignes de l'acte
 */
class ParService implements Scope
{
    public function __construct(protected string $chemin)
    {
    }

    public static function servicesAutorises(): ?array
    {
        $u = auth()->hasUser() ? auth()->user() : null;

        return $u && ! $u->estAdmin() && $u->service_id ? [(int) $u->service_id] : null;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $ids = static::servicesAutorises();
        if ($ids === null) {
            return;
        }

        $t = $model->getTable();
        $lignes = fn ($q) => $q->select('id')->from('lignes_credit')->whereIn('service_id', $ids);
        $engagements = fn ($q) => $q->select('id')->from('engagements')->whereIn('ligne_credit_id', $lignes);
        $liquidations = fn ($q) => $q->select('id')->from('liquidations')->whereIn('engagement_id', $engagements);

        match ($this->chemin) {
            'service' => $builder->whereIn("$t.service_id", $ids),
            'ligne' => $builder->whereIn("$t.ligne_credit_id", $lignes),
            'engagement' => $builder->whereIn("$t.engagement_id", $engagements),
            'liquidation' => $builder->whereIn("$t.liquidation_id", $liquidations),
            'prevision' => $builder->whereIn("$t.prevision_recette_id", fn ($q) => $q->select('id')->from('previsions_recette')->whereIn('service_id', $ids)),
            'modification' => $builder->whereIn("$t.id", fn ($q) => $q->select('modification_id')->from('modification_lignes')->whereIn('ligne_credit_id', $lignes)),
        };
    }
}
