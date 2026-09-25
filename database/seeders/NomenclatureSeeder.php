<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Nature;
use Illuminate\Database\Seeder;

/**
 * Classification économique de base (dépenses par titre, recettes par catégorie),
 * chaque nature étant rattachée à son compte d'imputation comptable.
 * À compléter ou adapter à la nomenclature budgétaire officielle depuis Paramètres › Nomenclature économique.
 */
class NomenclatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::natures() as [$type, $code, $libelle, $titre, $compte]) {
            Nature::updateOrCreate(['code' => $code], [
                'type' => $type,
                'libelle' => $libelle,
                'titre' => $titre,
                'compte_id' => Compte::where('numero', $compte)->value('id'),
                'actif' => true,
            ]);
        }
    }

    public static function natures(): array
    {
        return [
            // Titre 1 — Charges financières de la dette
            ['depense', '671', 'Intérêts et frais financiers de la dette', 1, '671'],
            // Titre 2 — Dépenses de personnel
            ['depense', '661', 'Traitements et salaires', 2, '661'],
            ['depense', '663', 'Indemnités et primes', 2, '663'],
            ['depense', '664', 'Cotisations sociales', 2, '664'],
            // Titre 3 — Acquisitions de biens et services
            ['depense', '6051', 'Eau', 3, '6051'],
            ['depense', '6052', 'Électricité', 3, '6052'],
            ['depense', '6053', 'Carburant et lubrifiants', 3, '6053'],
            ['depense', '6055', 'Fournitures de bureau', 3, '6055'],
            ['depense', '6056', 'Petit matériel et outillage', 3, '6056'],
            ['depense', '622', 'Loyers et charges locatives', 3, '622'],
            ['depense', '624', 'Entretien et maintenance', 3, '624'],
            ['depense', '625', "Primes d'assurance", 3, '625'],
            ['depense', '628', 'Télécommunications', 3, '628'],
            ['depense', '632', 'Études, honoraires et conseils', 3, '632'],
            ['depense', '633', 'Formation des agents', 3, '633'],
            ['depense', '6381', 'Frais de mission et de déplacement', 3, '6381'],
            // Titre 4 — Transferts courants
            ['depense', '6581', 'Transferts aux établissements publics', 4, '6581'],
            ['depense', '6582', 'Transferts aux collectivités territoriales', 4, '6582'],
            ['depense', '6583', 'Bourses et aides sociales', 4, '6583'],
            // Titre 5 — Investissements exécutés par l'État
            ['depense', '213', 'Logiciels et applications', 5, '213'],
            ['depense', '231', 'Bâtiments administratifs', 5, '231'],
            ['depense', '2441', 'Matériel et mobilier de bureau', 5, '2441'],
            ['depense', '2442', 'Matériel informatique', 5, '2442'],
            ['depense', '245', 'Matériel de transport', 5, '245'],
            // Titre 6 — Transferts en capital
            ['depense', '6585', "Subventions d'investissement versées", 6, '6585'],

            // Recettes
            ['recette', '7064', 'Redevances et droits administratifs', 2, '7064'],
            ['recette', '7065', "Frais de concours et d'inscription", 2, '7065'],
            ['recette', '7581', 'Produits domaniaux et loyers perçus', 2, '7581'],
            ['recette', '711', 'Dons et subventions reçus', 3, '711'],
        ];
    }
}
