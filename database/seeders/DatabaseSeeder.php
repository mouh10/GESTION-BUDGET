<?php

namespace Database\Seeders;

use App\Models\Exercice;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PlanComptableSeeder::class, NomenclatureSeeder::class]);

        $journaux = [
            ['DP', 'Journal des dépenses (mandats)', 'operations_diverses'],
            ['RC', 'Journal des recettes (titres)', 'operations_diverses'],
            ['TR', 'Journal du Trésor', 'banque'],
            ['BQ', 'Journal de banque', 'banque'],
            ['RG', 'Journal de la régie', 'caisse'],
            ['OD', 'Opérations diverses', 'operations_diverses'],
            ['AN', 'À-nouveaux', 'operations_diverses'],
        ];
        foreach ($journaux as [$code, $libelle, $type]) {
            Journal::updateOrCreate(['code' => $code], ['libelle' => $libelle, 'type' => $type]);
        }

        User::updateOrCreate(['email' => 'admin@gestion.test'], [
            'name' => 'Administrateur',
            'password' => 'password',
            'role' => 'admin',
            'actif' => true,
        ]);

        $annee = (int) now()->format('Y');
        Exercice::firstOrCreate(['libelle' => 'Gestion '.$annee], [
            'date_debut' => "{$annee}-01-01",
            'date_fin' => "{$annee}-12-31",
            'cloture' => false,
        ]);

        if (filter_var(env('GESTION_DEMO', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoSeeder::class);
        }
    }
}
