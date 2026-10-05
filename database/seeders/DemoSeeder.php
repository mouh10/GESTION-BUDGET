<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\Compte;
use App\Models\CompteTresorerie;
use App\Models\Engagement;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\LigneCredit;
use App\Models\Marche;
use App\Models\Modification;
use App\Models\Nature;
use App\Models\PrevisionRecette;
use App\Models\Programme;
use App\Models\Service;
use App\Models\Tiers;
use App\Models\User;
use App\Services\Comptabilite;
use App\Services\Credits;
use App\Services\Depenses;
use App\Services\Recettes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Données de démonstration : un ministère fictif avec trois programmes,
 * ses crédits, sa chaîne de la dépense, ses recettes et ses modifications budgétaires.
 * Désactivable avec GESTION_DEMO=false dans le fichier .env.
 */
class DemoSeeder extends Seeder
{
    protected Depenses $depenses;

    protected User $controleur;

    protected Carbon $aujourdhui;

    public function run(Comptabilite $compta, Credits $credits, Depenses $depenses, Recettes $recettes): void
    {
        if (Engagement::exists()) {
            return;
        }

        $this->depenses = $depenses;
        $this->aujourdhui = now()->startOfDay();

        $u = fn ($email, $nom, $role) => User::updateOrCreate(['email' => $email], ['name' => $nom, 'password' => 'password', 'role' => $role, 'actif' => true]);
        $u('ordonnateur@gestion.test', 'Fatou Ndiaye', 'ordonnateur');
        $this->controleur = $u('controleur@gestion.test', 'Ibrahima Sarr', 'controleur');
        $u('comptable@gestion.test', 'Awa Diop', 'comptable');
        $u('lecteur@gestion.test', 'Moussa Fall', 'lecteur');

        $exercice = Exercice::orderByDesc('date_debut')->first();
        $annee = (int) $exercice->date_debut->format('Y');
        $moisMax = (int) now()->format('Y') === $annee ? (int) now()->format('n') : 12;
        $compte = fn (string $n) => Compte::where('numero', $n)->value('id');
        $nature = fn (string $c) => Nature::where('code', $c)->value('id');

        /* ------------------------- Nomenclature ------------------------- */
        $services = collect([
            ['DAGE', "Direction de l'Administration générale et de l'Équipement", 'M. Diallo'],
            ['DRH', 'Direction des Ressources humaines', 'Mme Ba'],
            ['DSI', 'Direction des Systèmes d’information', 'M. Gueye'],
        ])->mapWithKeys(fn ($s) => [$s[0] => Service::create(['code' => $s[0], 'libelle' => $s[1], 'responsable' => $s[2]])]);

        // Gestionnaire dont le périmètre est limité à la DRH
        $u('drh@gestion.test', 'Khady Faye (DRH)', 'ordonnateur')->update(['service_id' => $services['DRH']->id]);

        $actions = [];
        foreach ([
            ['1001', 'Pilotage, coordination et gestion administrative', 'Secrétaire général', "Assurer le pilotage stratégique et la gestion efficiente des ressources du ministère.", [
                ['01', 'Gestion des ressources humaines'], ['02', 'Gestion financière et matérielle'],
            ]],
            ['1002', 'Modernisation et digitalisation des services publics', 'Directeur des Systèmes d’information', 'Dématérialiser les procédures administratives et moderniser les infrastructures numériques.', [
                ['01', 'Infrastructures numériques'],
            ]],
            ['1003', 'Renforcement des capacités des agents', 'Directeur des Ressources humaines', 'Améliorer les compétences des agents publics.', [
                ['01', 'Formation continue'],
            ]],
        ] as [$code, $libelle, $resp, $objectif, $acts]) {
            $programme = Programme::create(['code' => $code, 'libelle' => $libelle, 'responsable' => $resp, 'objectif' => $objectif]);
            foreach ($acts as [$ac, $al]) {
                $actions["$code.$ac"] = Action::create(['programme_id' => $programme->id, 'code' => $ac, 'libelle' => $al]);
            }
        }

        /* ------------------------- Crédits votés (quelques lignes) ------------------------- */
        $M = 1000000;
        $lignes = [];
        foreach ([
            // action, service, nature, source, AE, CP (en millions)
            ['1001.01', 'DRH', '661', 'etat', 480, 480],
            ['1001.01', 'DRH', '664', 'etat', 72, 72],
            ['1001.02', 'DAGE', '6052', 'etat', 36, 36],
            ['1001.02', 'DAGE', '6055', 'etat', 18, 18],
            ['1001.02', 'DAGE', '622', 'etat', 48, 48],
            ['1001.02', 'DAGE', '6381', 'etat', 25, 25],
            ['1002.01', 'DSI', '2442', 'etat', 180, 150],
            ['1003.01', 'DRH', '633', 'etat', 45, 45],
        ] as [$a, $s, $n, $src, $ae, $cp]) {
            $lignes["$a|$n|$src"] = LigneCredit::create([
                'exercice_id' => $exercice->id, 'action_id' => $actions[$a]->id, 'service_id' => $services[$s]->id,
                'nature_id' => $nature($n), 'source' => $src, 'ae_initiale' => $ae * $M, 'cp_initial' => $cp * $M,
            ]);
        }
        $L = fn ($a, $n, $src = 'etat') => $lignes["$a|$n|$src"];

        /* ------------------------- Tiers ------------------------- */
        $f = fn ($code, $nom, $compteNum = '4011', $type = 'fournisseur', $adresse = 'Dakar') => Tiers::create([
            'type' => $type, 'code' => $code, 'nom' => $nom, 'adresse' => $adresse, 'compte_id' => $compte($compteNum),
            'ninea' => $type === 'fournisseur' ? str_pad((string) mt_rand(1000000, 9999999), 9, '0', STR_PAD_LEFT) : null,
        ]);
        mt_srand(2026);
        $personnel = $f('FRS-000', "Personnel de l'État (états de paie)", '422');
        $caisseSociale = $f('FRS-001', 'Caisse de sécurité sociale (cotisations)', '431');
        $electricite = $f('FRS-002', "Compagnie d'électricité");
        $bailleur = $f('FRS-005', 'Immobilière du Plateau');
        $papeterie = $f('FRS-007', 'Papeterie Moderne SARL');
        $informatique = $f('FRS-008', 'Informatique Services SARL');
        $voyages = $f('FRS-011', 'Agence de Voyages Horizon');
        $cabinet = $f('FRS-013', 'Cabinet Conseil & Audit');

        $usagers = $f('RDV-001', 'Usagers des services (redevances)', '4111', 'redevable');
        $candidats = $f('RDV-002', 'Candidats aux concours administratifs', '4111', 'redevable');

        /* ------------------------- Trésorerie ------------------------- */
        $tresor = CompteTresorerie::create(['nom' => 'Compte de dépôt au Trésor', 'type' => 'tresor', 'numero' => 'Trésor public', 'compte_id' => $compte('532'), 'journal_id' => Journal::parCode('TR')->id, 'solde_initial' => 1000 * $M]);
        CompteTresorerie::create(['nom' => "Régie d'avances", 'type' => 'regie', 'compte_id' => $compte('581'), 'journal_id' => Journal::parCode('RG')->id, 'solde_initial' => 0]);
        $compta->ouvertureTresorerie($tresor, $compte('121'), $exercice->date_debut->toDateString());

        /* ------------------------- Marché ------------------------- */
        $mInfo = Marche::create([
            'exercice_id' => $exercice->id, 'numero' => "F-$annee-001/CPM", 'objet' => 'Acquisition de matériel informatique pour les directions',
            'tiers_id' => $informatique->id, 'type' => 'fournitures', 'mode_passation' => 'aoo', 'montant' => 120 * $M,
            'date_signature' => "$annee-02-15", 'ligne_credit_id' => $L('1002.01', '2442')->id,
        ]);

        /* ------------------------- Recettes ------------------------- */
        $prev = fn ($n, $s, $montant) => PrevisionRecette::create(['exercice_id' => $exercice->id, 'service_id' => $services[$s]->id, 'nature_id' => $nature($n), 'montant_prevu' => $montant]);
        $pRedevances = $prev('7064', 'DAGE', 45 * $M);
        $pConcours = $prev('7065', 'DRH', 30 * $M);

        $jour = fn (int $m, int $j) => Carbon::create($annee, max(1, min(12, $m)), 1)->addDays($j - 1)->min(Carbon::create($annee, max(1, min(12, $m)), 1)->endOfMonth())->min($this->aujourdhui)->toDateString();
        $milieu = max(1, intdiv($moisMax, 2));

        /* ------------------------- Dépenses : un exemple par étape de la chaîne ------------------------- */
        $this->chaine($L('1001.01', '661'), $personnel, $jour(1, 20), 39.5 * $M, 'Salaires du mois de janvier', 'salaires', 'paye');
        $this->chaine($L('1001.01', '661'), $personnel, $jour($milieu, 20), 39.5 * $M, 'Salaires du mois de '.Carbon::create($annee, $milieu)->translatedFormat('F'), 'salaires', 'paye');
        $this->chaine($L('1001.02', '622'), $bailleur, $jour(2, 2), 15 * $M, 'Loyer des bureaux (1er trimestre)', 'decision', 'paye');
        $this->chaine($L('1003.01', '633'), $cabinet, $jour(4, 8), 18 * $M, 'Formation des agents à la gestion axée sur les résultats', 'bon_commande', 'paye');
        $this->chaine($L('1001.01', '661'), $personnel, $jour($moisMax, 20), 39.5 * $M, 'Salaires du mois de '.Carbon::create($annee, $moisMax)->translatedFormat('F'), 'salaires', 'mandate');
        $this->chaine($L('1001.02', '6052'), $electricite, $jour($moisMax, 5), 3 * $M, 'Facture d’électricité', 'bon_commande', 'pris_en_charge');
        $this->chaine($L('1001.02', '6055'), $papeterie, $jour($moisMax, 3), 2.4 * $M, 'Fournitures de bureau', 'bon_commande', 'liquide');
        $this->chaine($L('1001.01', '664'), $caisseSociale, $jour($moisMax, 22), 5.9 * $M, 'Cotisations sociales du mois', 'decision', 'soumis');
        $this->chaine($L('1001.02', '6381'), $voyages, $jour($moisMax, 1), 8 * $M, 'Mission à l’étranger (pièces incomplètes)', 'mission', 'rejete');
        $this->chaine($L('1001.02', '6381'), $voyages, $jour($moisMax, 4), 2.5 * $M, 'Billets pour la mission de supervision', 'mission', 'brouillon');

        // Marché : engagé en totalité, une livraison payée, le solde reste à liquider
        $this->chaine($L('1002.01', '2442'), $informatique, $jour(3, 10), 120 * $M, $mInfo->objet, 'marche', 'vise', $mInfo);
        $this->liquiderEtPayer(Engagement::where('marche_id', $mInfo->id)->first(), $jour(5, 5), 60 * $M, 'Livraison n°1 (50 %)', 'paye');

        /* ------------------------- Recettes ------------------------- */
        $t = $recettes->emettre(['prevision_recette_id' => $pRedevances->id, 'tiers_id' => $usagers->id, 'date' => $jour(2, 3), 'objet' => 'Redevances administratives du trimestre', 'montant' => 11 * $M]);
        $recettes->recouvrer($t, $tresor, ['date' => $jour(3, 25), 'montant' => $t->montant, 'mode' => 'especes']);
        $t = $recettes->emettre(['prevision_recette_id' => $pConcours->id, 'tiers_id' => $candidats->id, 'date' => $jour($milieu, 2), 'objet' => "Frais d'inscription aux concours", 'montant' => 22 * $M]);
        $recettes->recouvrer($t, $tresor, ['date' => $jour($milieu, 28), 'montant' => 18.5 * $M, 'mode' => 'especes']);
        $recettes->emettre(['prevision_recette_id' => $pRedevances->id, 'tiers_id' => $usagers->id, 'date' => $jour($moisMax, 3), 'objet' => 'Redevances administratives du mois', 'montant' => 3.6 * $M]);

        /* ------------------------- Modifications budgétaires ------------------------- */
        $acte = function (string $type, string $date, string $ref, string $motif, array $mouvements, bool $approuver) use ($exercice, $credits) {
            $modif = Modification::create([
                'exercice_id' => $exercice->id,
                'numero' => \App\Services\Numerotation::suivant('MB-'.$exercice->annee().'-', 'modifications', 'numero'),
                'date' => $date, 'type' => $type, 'reference_acte' => $ref, 'motif' => $motif,
            ]);
            foreach ($mouvements as [$ligne, $ae, $cp]) {
                $modif->lignes()->create(['ligne_credit_id' => $ligne->id, 'ae' => $ae, 'cp' => $cp]);
            }
            if ($approuver) {
                Carbon::setTestNow(Carbon::parse($date)->setTime(12, 0));
                $credits->approuver($modif);
                Carbon::setTestNow();
            }
        };
        $acte('virement', $jour(4, 15), "Arrêté n° 00{$annee}-041", 'Renforcement des crédits de mission', [
            [$L('1001.02', '6055'), -3 * $M, -3 * $M], [$L('1001.02', '6381'), 3 * $M, 3 * $M],
        ], true);
        $acte('gel', $jour(6, 1), 'Circulaire de régulation budgétaire', 'Mise en réserve de crédits de loyer', [
            [$L('1001.02', '622'), 4 * $M, 4 * $M],
        ], true);
        $acte('transfert', $jour($moisMax, 2), "Projet de décret n° {$annee}-xxx", 'Transfert de crédits de formation vers les infrastructures numériques', [
            [$L('1003.01', '633'), -5 * $M, -5 * $M], [$L('1002.01', '2442'), 5 * $M, 5 * $M],
        ], false);
    }

    /**
     * Fait avancer une dépense jusqu'à l'étape voulue :
     * brouillon, soumis, rejete, vise, liquide, mandate, pris_en_charge, paye.
     */
    protected function chaine(LigneCredit $ligne, Tiers $tiers, string $date, float $montant, string $objet, string $type, string $etape, ?Marche $marche = null): Engagement
    {
        $e = $this->depenses->enregistrerEngagement([
            'ligne_credit_id' => $ligne->id, 'tiers_id' => $tiers->id, 'marche_id' => $marche?->id,
            'date' => $date, 'type' => $type, 'objet' => $objet, 'montant' => $montant,
        ]);
        if ($etape === 'brouillon') {
            return $e;
        }
        Carbon::setTestNow(Carbon::parse($date)->setTime(9, 0));   // horodatages cohérents avec les dates des pièces
        $this->depenses->soumettre($e);
        Carbon::setTestNow();
        if ($etape === 'soumis') {
            return $e;
        }
        if ($etape === 'rejete') {
            $this->depenses->rejeter($e, 'Pièces justificatives incomplètes : ordre de mission non signé.');

            return $e;
        }
        Carbon::setTestNow(Carbon::parse($date)->addDay()->min($this->aujourdhui)->setTime(11, 0));
        $this->depenses->viser($e, $this->controleur);
        Carbon::setTestNow();
        if ($etape !== 'vise') {
            $this->liquiderEtPayer($e, $date, $montant, null, $etape);
        }

        return $e;
    }

    protected function liquiderEtPayer(Engagement $e, string $date, float $montant, ?string $reference, string $etape, ?CompteTresorerie $tresorerie = null): void
    {
        $d = fn (int $n) => Carbon::parse($date)->addDays($n)->min($this->aujourdhui)->min(Carbon::parse($date)->endOfYear())->toDateString();

        $liq = $this->depenses->liquider($e, ['date' => $d(3), 'montant' => $montant, 'reference_facture' => $reference ?? 'Facture '.strtoupper(substr(md5($e->numero.$montant), 0, 6)), 'date_service_fait' => $d(2)]);
        if ($etape === 'liquide') {
            return;
        }
        $mandat = $this->depenses->emettreMandat($liq, $d(5));
        if ($etape === 'mandate') {
            return;
        }
        Carbon::setTestNow(Carbon::parse($d(7))->setTime(10, 0));
        $this->depenses->prendreEnCharge($mandat, $d(7));
        Carbon::setTestNow();
        if ($etape === 'pris_en_charge') {
            return;
        }
        $this->depenses->payer($mandat->fresh(), $tresorerie ?? CompteTresorerie::where('type', 'tresor')->first(), ['date' => $d(10), 'mode' => 'virement', 'reference' => 'OP-'.$mandat->numero]);
    }
}
