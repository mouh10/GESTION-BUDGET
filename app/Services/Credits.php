<?php

namespace App\Services;

use App\Exceptions\GestionException;
use App\Models\Exercice;
use App\Models\LigneCredit;
use App\Models\Modification;
use App\Models\Nature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Situation des crédits : dotations, modifications, réserve, consommation et disponible,
 * et approbation des actes de modification budgétaire.
 *
 * Règles :
 *  - AE disponibles = AE révisées − AE gelées − engagements (soumis ou visés)
 *  - CP disponibles = CP révisés − CP gelés − mandats (émis, pris en charge ou payés)
 */
class Credits
{
    /**
     * Situation de chaque ligne de crédits de l'exercice.
     *
     * @param  array  $filtres  programme_id, action_id, service_id, titre, source, ids
     * @return Collection<int, object>
     */
    public function situation(Exercice $exercice, array $filtres = [], ?string $au = null): Collection
    {
        $lignes = LigneCredit::with('action.programme', 'service', 'nature')
            ->where('exercice_id', $exercice->id)
            ->when($filtres['programme_id'] ?? null, fn ($q, $v) => $q->whereHas('action', fn ($a) => $a->where('programme_id', $v)))
            ->when($filtres['action_id'] ?? null, fn ($q, $v) => $q->where('action_id', $v))
            ->when($filtres['service_id'] ?? null, fn ($q, $v) => $q->where('service_id', $v))
            ->when($filtres['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filtres['titre'] ?? null, fn ($q, $v) => $q->whereHas('nature', fn ($n) => $n->where('titre', $v)))
            ->when($filtres['ids'] ?? null, fn ($q, $v) => $q->whereIn('id', (array) $v))
            ->get();

        if ($lignes->isEmpty()) {
            return collect();
        }

        $ids = $lignes->pluck('id')->all();

        $modifs = DB::table('modification_lignes as ml')
            ->join('modifications as m', 'm.id', '=', 'ml.modification_id')
            ->where('m.statut', 'approuve')
            ->whereIn('ml.ligne_credit_id', $ids)
            ->when($au, fn ($q) => $q->whereDate('m.date', '<=', $au))
            ->groupBy('ml.ligne_credit_id', 'm.type')
            ->selectRaw('ml.ligne_credit_id as id, m.type, SUM(ml.ae) as ae, SUM(ml.cp) as cp')
            ->get()->groupBy('id');

        $engagements = DB::table('engagements')
            ->whereIn('ligne_credit_id', $ids)
            ->whereIn('statut', ['soumis', 'vise'])
            ->when($au, fn ($q) => $q->whereDate('date', '<=', $au))
            ->groupBy('ligne_credit_id', 'statut')
            ->selectRaw('ligne_credit_id as id, statut, SUM(montant) as total')
            ->get()->groupBy('id');

        $liquidations = DB::table('liquidations as l')
            ->join('engagements as e', 'e.id', '=', 'l.engagement_id')
            ->whereIn('e.ligne_credit_id', $ids)
            ->where('l.statut', 'validee')
            ->when($au, fn ($q) => $q->whereDate('l.date', '<=', $au))
            ->groupBy('e.ligne_credit_id')
            ->selectRaw('e.ligne_credit_id as id, SUM(l.montant) as total')
            ->pluck('total', 'id');

        $mandats = DB::table('mandats as md')
            ->join('liquidations as l', 'l.id', '=', 'md.liquidation_id')
            ->join('engagements as e', 'e.id', '=', 'l.engagement_id')
            ->whereIn('e.ligne_credit_id', $ids)
            ->where('md.statut', '!=', 'rejete')
            ->when($au, fn ($q) => $q->whereDate('md.date', '<=', $au))
            ->groupBy('e.ligne_credit_id', 'md.statut')
            ->selectRaw('e.ligne_credit_id as id, md.statut, SUM(md.montant) as total')
            ->get()->groupBy('id');

        return $lignes->map(function (LigneCredit $ligne) use ($modifs, $engagements, $liquidations, $mandats) {
            $m = $modifs->get($ligne->id, collect());
            $somme = fn (array $types, string $col) => round((float) $m->whereIn('type', $types)->sum($col), 2);

            $aeModif = $somme(['virement', 'transfert', 'ouverture', 'annulation'], 'ae');
            $cpModif = $somme(['virement', 'transfert', 'ouverture', 'annulation'], 'cp');
            $aeGelee = round($somme(['gel'], 'ae') - $somme(['degel'], 'ae'), 2);
            $cpGele = round($somme(['gel'], 'cp') - $somme(['degel'], 'cp'), 2);

            $e = $engagements->get($ligne->id, collect());
            $engageVise = round((float) $e->where('statut', 'vise')->sum('total'), 2);
            $engage = round((float) $e->sum('total'), 2);

            $md = $mandats->get($ligne->id, collect());
            $ordonnance = round((float) $md->sum('total'), 2);
            $paye = round((float) $md->where('statut', 'paye')->sum('total'), 2);

            $aeRevisee = round((float) $ligne->ae_initiale + $aeModif, 2);
            $cpRevise = round((float) $ligne->cp_initial + $cpModif, 2);

            return (object) [
                'ligne' => $ligne,
                'programme' => $ligne->action->programme,
                'action' => $ligne->action,
                'service' => $ligne->service,
                'nature' => $ligne->nature,
                'titre' => $ligne->nature->titre,
                'ae_initiale' => (float) $ligne->ae_initiale,
                'cp_initial' => (float) $ligne->cp_initial,
                'ae_modif' => $aeModif,
                'cp_modif' => $cpModif,
                'ae_revisee' => $aeRevisee,
                'cp_revise' => $cpRevise,
                'ae_gelee' => $aeGelee,
                'cp_gele' => $cpGele,
                'engage' => $engage,
                'engage_vise' => $engageVise,
                'liquide' => round((float) ($liquidations[$ligne->id] ?? 0), 2),
                'ordonnance' => $ordonnance,
                'paye' => $paye,
                'ae_disponible' => round($aeRevisee - $aeGelee - $engage, 2),
                'cp_disponible' => round($cpRevise - $cpGele - $ordonnance, 2),
                'taux_engagement' => $aeRevisee > 0 ? round($engage / $aeRevisee * 100, 1) : null,
                'taux_ordonnancement' => $cpRevise > 0 ? round($ordonnance / $cpRevise * 100, 1) : null,
                'taux_paiement' => $cpRevise > 0 ? round($paye / $cpRevise * 100, 1) : null,
            ];
        })->sortBy(fn ($r) => $r->programme->code.'|'.$r->action->code.'|'.$r->nature->code.'|'.$r->service->code.'|'.$r->ligne->source)->values();
    }

    public function pourLigne(LigneCredit $ligne): object
    {
        return $this->situation($ligne->exercice, ['ids' => [$ligne->id]])->first();
    }

    /** Totaux d'une collection de situations. */
    public function totaux(Collection $situation): array
    {
        $cles = ['ae_initiale', 'cp_initial', 'ae_modif', 'cp_modif', 'ae_revisee', 'cp_revise', 'ae_gelee', 'cp_gele',
            'engage', 'engage_vise', 'liquide', 'ordonnance', 'paye', 'ae_disponible', 'cp_disponible'];

        $t = [];
        foreach ($cles as $c) {
            $t[$c] = round($situation->sum($c), 2);
        }
        $t['taux_engagement'] = $t['ae_revisee'] > 0 ? round($t['engage'] / $t['ae_revisee'] * 100, 1) : null;
        $t['taux_ordonnancement'] = $t['cp_revise'] > 0 ? round($t['ordonnance'] / $t['cp_revise'] * 100, 1) : null;
        $t['taux_paiement'] = $t['cp_revise'] > 0 ? round($t['paye'] / $t['cp_revise'] * 100, 1) : null;

        return $t;
    }

    /**
     * Regroupe la situation par programme, titre, service ou source.
     *
     * @return Collection<int, object{cle:string, libelle:string, totaux:array, lignes:Collection}>
     */
    public function regrouper(Collection $situation, string $par): Collection
    {
        return $situation->groupBy(fn ($r) => match ($par) {
            'titre' => (string) $r->titre,
            'service' => $r->service->code,
            'source' => $r->ligne->source,
            'action' => $r->programme->code.'.'.$r->action->code,
            default => $r->programme->code,
        })->map(function ($lignes, $cle) use ($par) {
            $premier = $lignes->first();

            return (object) [
                'cle' => (string) $cle,
                'libelle' => match ($par) {
                    'titre' => 'Titre '.$cle.' — '.(Nature::TITRES_DEPENSE[(int) $cle] ?? ''),
                    'service' => $premier->service->intitule,
                    'source' => LigneCredit::SOURCES[$cle] ?? $cle,
                    'action' => $premier->programme->code.'.'.$premier->action->code.' — '.$premier->action->libelle,
                    default => $premier->programme->intitule,
                },
                'totaux' => $this->totaux($lignes),
                'lignes' => $lignes->values(),
            ];
        })->sortKeys()->values();
    }

    /* ------------------------------------------------------------------
     |  Modifications budgétaires
     * ------------------------------------------------------------------ */

    public function approuver(Modification $modification): Modification
    {
        if ($modification->estApprouvee()) {
            throw new GestionException('Cet acte est déjà approuvé.');
        }

        $modification->load('lignes.ligneCredit.action', 'exercice');
        if ($modification->exercice->cloture) {
            throw new GestionException("L'exercice est clôturé.");
        }
        if ($modification->lignes->isEmpty()) {
            throw new GestionException("L'acte ne comporte aucune ligne.");
        }

        $type = $modification->type;
        $totalAe = round($modification->lignes->sum('ae'), 2);
        $totalCp = round($modification->lignes->sum('cp'), 2);

        foreach ($modification->lignes as $l) {
            if ((float) $l->ae == 0.0 && (float) $l->cp == 0.0) {
                throw new GestionException('Chaque ligne doit comporter un montant en AE ou en CP.');
            }
            if ($l->ligneCredit->exercice_id !== $modification->exercice_id) {
                throw new GestionException("Une ligne n'appartient pas à l'exercice de l'acte.");
            }
        }

        switch ($type) {
            case 'virement':
            case 'transfert':
                if (abs($totalAe) > 0.004 || abs($totalCp) > 0.004) {
                    throw new GestionException('Un '.$type.' doit être équilibré : total des diminutions = total des augmentations (AE et CP).');
                }
                if ($type === 'virement' && $modification->lignes->pluck('ligneCredit.action.programme_id')->unique()->count() > 1) {
                    throw new GestionException("Un virement se fait au sein d'un même programme. Utilisez un transfert entre programmes.");
                }
                break;
            case 'ouverture':
            case 'gel':
            case 'degel':
                if ($modification->lignes->contains(fn ($l) => $l->ae < 0 || $l->cp < 0)) {
                    throw new GestionException('Les montants d’un acte de type « '.Modification::TYPES_COURTS[$type].' » doivent être positifs.');
                }
                break;
            case 'annulation':
                if ($modification->lignes->contains(fn ($l) => $l->ae > 0 || $l->cp > 0)) {
                    throw new GestionException('Une annulation ne comporte que des montants négatifs (diminutions).');
                }
                break;
        }

        // Contrôle : aucune ligne ne doit devenir négative en disponible (ou en réserve pour un dégel).
        $situation = $this->situation($modification->exercice, ['ids' => $modification->lignes->pluck('ligne_credit_id')->all()])
            ->keyBy(fn ($r) => $r->ligne->id);

        foreach ($modification->lignes->groupBy('ligne_credit_id') as $ligneId => $mouvements) {
            $s = $situation[$ligneId];
            $ae = round($mouvements->sum('ae'), 2);
            $cp = round($mouvements->sum('cp'), 2);
            $imputation = $s->ligne->imputation();

            if ($type === 'degel') {
                if ($ae - $s->ae_gelee > 0.004 || $cp - $s->cp_gele > 0.004) {
                    throw new GestionException("Dégel supérieur à la réserve de la ligne {$imputation}.");
                }

                continue;
            }

            $effetAe = $type === 'gel' ? -$ae : $ae;
            $effetCp = $type === 'gel' ? -$cp : $cp;

            if ($s->ae_disponible + $effetAe < -0.004) {
                throw new GestionException("AE disponibles insuffisantes sur {$imputation} (disponible : ".fcfa($s->ae_disponible).').');
            }
            if ($s->cp_disponible + $effetCp < -0.004) {
                throw new GestionException("CP disponibles insuffisants sur {$imputation} (disponible : ".fcfa($s->cp_disponible).').');
            }
        }

        $modification->update(['statut' => 'approuve', 'approuve_le' => now()]);

        return $modification;
    }
}
