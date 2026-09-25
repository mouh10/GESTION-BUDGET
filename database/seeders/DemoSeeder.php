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
            ['CPM', 'Cellule de Passation des Marchés', 'Mme Sy'],
        ])->mapWithKeys(fn ($s) => [$s[0] => Service::create(['code' => $s[0], 'libelle' => $s[1], 'responsable' => $s[2]])]);

        $actions = [];
        foreach ([
            ['1001', 'Pilotage, coordination et gestion administrative', 'Secrétaire général', "Assurer le pilotage stratégique et la gestion efficiente des ressources du ministère.", [
                ['01', 'Coordination et pilotage'], ['02', 'Gestion des ressources humaines'], ['03', 'Gestion financière et matérielle'],
            ]],
            ['1002', 'Modernisation et digitalisation des services publics', 'Directeur des Systèmes d’information', 'Dématérialiser les procédures administratives et moderniser les infrastructures numériques.', [
                ['01', 'Dématérialisation des procédures'], ['02', 'Infrastructures numériques'],
            ]],
            ['1003', 'Renforcement des capacités des agents', 'Directeur des Ressources humaines', 'Améliorer les compétences des agents publics.', [
                ['01', 'Formation continue'], ['02', 'Bourses et appui aux écoles de formation'],
            ]],
        ] as [$code, $libelle, $resp, $objectif, $acts]) {
            $programme = Programme::create(['code' => $code, 'libelle' => $libelle, 'responsable' => $resp, 'objectif' => $objectif]);
            foreach ($acts as [$ac, $al]) {
                $actions["$code.$ac"] = Action::create(['programme_id' => $programme->id, 'code' => $ac, 'libelle' => $al]);
            }
        }

        /* ------------------------- Crédits votés ------------------------- */
        $M = 1000000;
        $lignes = [];
        foreach ([
            // action, service, nature, source, AE, CP (en millions)
            ['1001.02', 'DRH', '661', 'etat', 480, 480],
            ['1001.02', 'DRH', '663', 'etat', 90, 90],
            ['1001.02', 'DRH', '664', 'etat', 72, 72],
            ['1001.01', 'DAGE', '632', 'etat', 30, 30],
            ['1001.03', 'DAGE', '6051', 'etat', 6, 6],
            ['1001.03', 'DAGE', '6052', 'etat', 36, 36],
            ['1001.03', 'DAGE', '6053', 'etat', 24, 24],
            ['1001.03', 'DAGE', '6055', 'etat', 18, 18],
            ['1001.03', 'DAGE', '622', 'etat', 48, 48],
            ['1001.03', 'DAGE', '624', 'etat', 20, 20],
            ['1001.03', 'DAGE', '625', 'etat', 8, 8],
            ['1001.03', 'DAGE', '628', 'etat', 15, 15],
            ['1001.03', 'DAGE', '6381', 'etat', 25, 25],
            ['1001.03', 'DAGE', '245', 'etat', 90, 90],
            ['1001.03', 'DAGE', '231', 'etat', 400, 150],
            ['1002.01', 'DSI', '213', 'etat', 250, 120],
            ['1002.02', 'DSI', '2442', 'etat', 180, 150],
            ['1002.02', 'DSI', '2442', 'don', 200, 100],
            ['1003.01', 'DRH', '633', 'etat', 45, 45],
            ['1003.02', 'DRH', '6583', 'etat', 60, 60],
            ['1003.02', 'DAGE', '6581', 'etat', 150, 150],
            ['1003.02', 'DAGE', '6585', 'etat', 100, 50],
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
        $eau = $f('FRS-003', "Société de distribution d'eau");
        $carburant = $f('FRS-004', 'Station-service Plateau');
        $bailleur = $f('FRS-005', 'Immobilière du Plateau');
        $telecom = $f('FRS-006', 'Opérateur Télécom Pro');
        $papeterie = $f('FRS-007', 'Papeterie Moderne SARL');
        $informatique = $f('FRS-008', 'Informatique Services SARL');
        $digital = $f('FRS-009', 'Digital Solutions Afrique');
        $nettoyage = $f('FRS-010', 'Société de Nettoyage Teranga');
        $voyages = $f('FRS-011', 'Agence de Voyages Horizon');
        $ecole = $f('FRS-012', 'École nationale de formation administrative (EP)');
        $cabinet = $f('FRS-013', 'Cabinet Conseil & Audit');

        $usagers = $f('RDV-001', 'Usagers des services (redevances)', '4111', 'redevable');
        $candidats = $f('RDV-002', 'Candidats aux concours administratifs', '4111', 'redevable');
        $occupant = $f('RDV-003', 'Occupants de logements administratifs', '4111', 'redevable');
        $ptf = $f('RDV-004', 'Partenaire technique et financier (don)', '4111', 'redevable');

        /* ------------------------- Trésorerie ------------------------- */
        $tresor = CompteTresorerie::create(['nom' => 'Compte de dépôt au Trésor', 'type' => 'tresor', 'numero' => 'Trésor public', 'compte_id' => $compte('532'), 'journal_id' => Journal::parCode('TR')->id, 'solde_initial' => 1500 * $M]);
        $banqueDon = CompteTresorerie::create(['nom' => 'Compte spécial du don', 'type' => 'banque', 'numero' => 'SN000 01001 000123456789 00', 'compte_id' => $compte('521'), 'journal_id' => Journal::parCode('BQ')->id, 'solde_initial' => 0]);
        $regie = CompteTresorerie::create(['nom' => "Régie d'avances", 'type' => 'regie', 'compte_id' => $compte('581'), 'journal_id' => Journal::parCode('RG')->id, 'solde_initial' => 0]);
        $compta->ouvertureTresorerie($tresor, $compte('121'), $exercice->date_debut->toDateString());

        /* ------------------------- Marchés ------------------------- */
        $mc = fn ($num, $objet, $tiers, $type, $mode, $montant, $date, $ligne) => Marche::create([
            'exercice_id' => $exercice->id, 'numero' => $num, 'objet' => $objet, 'tiers_id' => $tiers->id, 'type' => $type,
            'mode_passation' => $mode, 'montant' => $montant, 'date_signature' => "$annee-$date", 'ligne_credit_id' => $ligne->id,
        ]);
        $mInfo = $mc("F-$annee-001/CPM", 'Acquisition de matériel informatique pour les directions', $informatique, 'fournitures', 'aoo', 140 * $M, '03-02', $L('1002.02', '2442'));
        $mPlateforme = $mc("PI-$annee-002/CPM", 'Développement de la plateforme de dématérialisation des procédures', $digital, 'prestations_intellectuelles', 'aoo', 110 * $M, '03-15', $L('1002.01', '213'));
        $mNettoyage = $mc("S-$annee-003/CPM", 'Entretien et nettoyage des locaux', $nettoyage, 'services', 'drp', 18 * $M, '01-10', $L('1001.03', '624'));
        $mDon = $mc("F-$annee-004/CPM", 'Équipement informatique des centres de services (financement don)', $informatique, 'fournitures', 'aoo', 95 * $M, '04-20', $L('1002.02', '2442', 'don'));

        /* ------------------------- Recettes ------------------------- */
        $prev = fn ($n, $s, $montant) => PrevisionRecette::create(['exercice_id' => $exercice->id, 'service_id' => $services[$s]->id, 'nature_id' => $nature($n), 'montant_prevu' => $montant]);
        $pRedevances = $prev('7064', 'DAGE', 45 * $M);
        $pConcours = $prev('7065', 'DRH', 30 * $M);
        $pLoyers = $prev('7581', 'DAGE', 12 * $M);
        $pDon = $prev('711', 'DSI', 200 * $M);

        $jour = fn (int $m, int $j) => Carbon::create($annee, $m, 1)->addDays($j - 1)->min(Carbon::create($annee, $m, 1)->endOfMonth())->min($this->aujourdhui)->toDateString();

        /* ------------------------- Exécution mensuelle ------------------------- */
        for ($m = 1; $m <= $moisMax; $m++) {
            $courant = $m === $moisMax;

            // Personnel : état de paie mensuel
            $this->chaine($L('1001.02', '661'), $personnel, $jour($m, 20), 39.5 * $M, "Salaires du mois de ".Carbon::create($annee, $m)->translatedFormat('F'), 'salaires', $courant ? 'mandate' : 'paye');
            $this->chaine($L('1001.02', '663'), $personnel, $jour($m, 20), 7.2 * $M, 'Indemnités et primes du mois', 'salaires', $courant ? 'vise' : 'paye');
            $this->chaine($L('1001.02', '664'), $caisseSociale, $jour($m, 22), 5.9 * $M, 'Cotisations sociales du mois', 'decision', $courant ? 'soumis' : 'paye');

            // Fonctionnement courant
            $this->chaine($L('1001.03', '6052'), $electricite, $jour($m, 5), mt_rand(26, 34) * 100000, 'Facture d’électricité', 'bon_commande', $courant ? 'pris_en_charge' : 'paye');
            $this->chaine($L('1001.03', '6051'), $eau, $jour($m, 6), mt_rand(4, 5) * 100000, 'Facture d’eau', 'bon_commande', 'paye');
            $this->chaine($L('1001.03', '622'), $bailleur, $jour($m, 2), 5 * $M, 'Loyer des bureaux', 'decision', 'paye');
            $this->chaine($L('1001.03', '628'), $telecom, $jour($m, 10), 1.25 * $M, 'Abonnements téléphone et internet', 'bon_commande', $courant ? 'liquide' : 'paye');
            $this->chaine($L('1001.03', '6053'), $carburant, $jour($m, 8), 2 * $M, 'Bons de carburant', 'bon_commande', 'paye');
            $this->chaine($L('1001.03', '624'), $nettoyage, $jour($m, 28), 1.5 * $M, 'Nettoyage des locaux (marché)', 'marche', $courant ? 'mandate' : 'paye', $mNettoyage);

            if ($m % 2 === 0) {
                $this->chaine($L('1001.03', '6055'), $papeterie, $jour($m, 12), mt_rand(20, 32) * 100000, 'Fournitures de bureau', 'bon_commande', 'paye');
                $this->chaine($L('1001.03', '6381'), $voyages, $jour($m, 14), mt_rand(25, 45) * 100000, 'Billets et frais de mission', 'mission', 'paye');
            }
            if ($m % 3 === 0) {
                $this->chaine($L('1003.02', '6581'), $ecole, $jour($m, 15), 37.5 * $M, 'Transfert trimestriel à l’école de formation', 'decision', 'paye');
                $this->chaine($L('1003.02', '6583'), $personnel, $jour($m, 16), 12 * $M, 'Bourses de formation du trimestre', 'decision', 'paye');
                $recettes->recouvrer($recettes->emettre(['prevision_recette_id' => $pLoyers->id, 'tiers_id' => $occupant->id, 'date' => $jour($m, 5), 'objet' => 'Loyers du trimestre', 'montant' => 3 * $M]), $tresor, ['date' => $jour($m, 20), 'montant' => 3 * $M, 'mode' => 'virement']);
            }

            // Redevances mensuelles
            $t = $recettes->emettre(['prevision_recette_id' => $pRedevances->id, 'tiers_id' => $usagers->id, 'date' => $jour($m, 3), 'objet' => 'Redevances administratives du mois', 'montant' => mt_rand(32, 40) * 100000]);
            if (! $courant) {
                $recettes->recouvrer($t, $tresor, ['date' => $jour($m, 25), 'montant' => $t->montant, 'mode' => 'especes']);
            }
        }

        // Marchés et investissements
        $this->chaine($L('1002.02', '2442'), $informatique, $jour(3, 10), 140 * $M, $mInfo->objet, 'marche', 'vise', $mInfo);
        $eInfo = Engagement::where('marche_id', $mInfo->id)->first();
        $this->liquiderEtPayer($eInfo, $jour(5, 5), 70 * $M, 'Livraison n°1 (50 %)', 'paye');
        if ($moisMax >= 9) {
            $this->liquiderEtPayer($eInfo, $jour(9, 3), 70 * $M, 'Livraison n°2 (solde)', 'pris_en_charge');
        }

        $this->chaine($L('1002.01', '213'), $digital, $jour(3, 20), 110 * $M, $mPlateforme->objet, 'marche', 'vise', $mPlateforme);
        $ePlat = Engagement::where('marche_id', $mPlateforme->id)->first();
        $this->liquiderEtPayer($ePlat, $jour(4, 25), 33 * $M, 'Avance de démarrage (30 %)', 'paye');
        $this->liquiderEtPayer($ePlat, $jour(7, 30), 22 * $M, 'Livrable 1 : cahier des charges validé', 'paye');

        $recettes->recouvrer($recettes->emettre(['prevision_recette_id' => $pDon->id, 'tiers_id' => $ptf->id, 'date' => $jour(4, 2), 'objet' => 'Première tranche du don', 'montant' => 100 * $M]), $banqueDon, ['date' => $jour(4, 10), 'montant' => 100 * $M, 'mode' => 'virement']);
        $this->chaine($L('1002.02', '2442', 'don'), $informatique, $jour(5, 2), 95 * $M, $mDon->objet, 'marche', 'vise', $mDon);
        $this->liquiderEtPayer(Engagement::where('marche_id', $mDon->id)->first(), $jour(7, 10), 60 * $M, 'Livraison partielle', 'paye', $banqueDon);

        $this->chaine($L('1001.03', '245'), $f('FRS-014', 'Garage Central Automobiles'), $jour(6, 12), 42 * $M, 'Acquisition de deux véhicules de liaison', 'bon_commande', 'paye');
        $this->chaine($L('1001.01', '632'), $cabinet, $jour(5, 18), 12 * $M, "Audit organisationnel des services", 'bon_commande', 'paye');
        $this->chaine($L('1003.01', '633'), $cabinet, $jour(4, 8), 18 * $M, 'Formation des agents à la gestion axée sur les résultats', 'bon_commande', 'paye');
        $this->chaine($L('1003.02', '6585'), $ecole, $jour(6, 30), 30 * $M, "Subvention d'équipement à l'école de formation", 'decision', 'mandate');

        // Engagement rejeté par le contrôle financier (exemple)
        $this->chaine($L('1001.03', '6381'), $voyages, $jour($moisMax, 1), 8 * $M, 'Mission à l’étranger (pièces incomplètes)', 'mission', 'rejete');

        // Concours administratifs
        $t = $recettes->emettre(['prevision_recette_id' => $pConcours->id, 'tiers_id' => $candidats->id, 'date' => $jour(5, 2), 'objet' => "Frais d'inscription aux concours", 'montant' => 22 * $M]);
        $recettes->recouvrer($t, $tresor, ['date' => $jour(5, 30), 'montant' => 18.5 * $M, 'mode' => 'especes']);

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
                $credits->approuver($modif);
            }
        };
        $acte('virement', $jour(4, 15), "Arrêté n° 00{$annee}-041", 'Renforcement des crédits de mission', [
            [$L('1001.03', '625'), -3 * $M, -3 * $M], [$L('1001.03', '6381'), 3 * $M, 3 * $M],
        ], true);
        $acte('gel', $jour(6, 1), 'Circulaire de régulation budgétaire', 'Mise en réserve de 10 % des crédits d’études', [
            [$L('1001.01', '632'), 3 * $M, 3 * $M],
        ], true);
        $acte('ouverture', $jour(7, 1), "LFR n° {$annee}-01", 'Ouverture de crédits pour la fibre optique des directions', [
            [$L('1002.02', '2442'), 40 * $M, 30 * $M],
        ], true);
        $acte('transfert', $jour(min($moisMax, 9), 2), "Projet de décret n° {$annee}-xxx", 'Transfert de crédits de formation vers la dématérialisation', [
            [$L('1003.01', '633'), -5 * $M, -5 * $M], [$L('1002.01', '213'), 5 * $M, 5 * $M],
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
        $this->depenses->soumettre($e);
        if ($etape === 'soumis') {
            return $e;
        }
        if ($etape === 'rejete') {
            $this->depenses->rejeter($e, 'Pièces justificatives incomplètes : ordre de mission non signé.');

            return $e;
        }
        $this->depenses->viser($e, $this->controleur);
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
        $this->depenses->prendreEnCharge($mandat, $d(7));
        if ($etape === 'pris_en_charge') {
            return;
        }
        $this->depenses->payer($mandat->fresh(), $tresorerie ?? CompteTresorerie::where('type', 'tresor')->first(), ['date' => $d(10), 'mode' => 'virement', 'reference' => 'OP-'.$mandat->numero]);
    }
}
