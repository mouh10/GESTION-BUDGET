<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index sur les colonnes les plus filtrées et jointes.
 * PostgreSQL n'indexe pas automatiquement les clés étrangères : sans ces index,
 * les situations d'exécution et les tableaux de bord ralentissent quand les données grossissent.
 */
return new class extends Migration
{
    private array $index = [
        'lignes_ecriture' => [['ecriture_id'], ['compte_id'], ['tiers_id']],
        'ecritures' => [['exercice_id', 'statut'], ['journal_id']],
        'lignes_credit' => [['action_id'], ['service_id'], ['nature_id']],
        'modification_lignes' => [['modification_id'], ['ligne_credit_id']],
        'modifications' => [['exercice_id', 'statut']],
        'marches' => [['exercice_id'], ['tiers_id']],
        'engagements' => [['exercice_id', 'statut'], ['ligne_credit_id', 'statut'], ['tiers_id'], ['marche_id'], ['date']],
        'liquidations' => [['engagement_id', 'statut']],
        'mandats' => [['liquidation_id', 'statut'], ['date_paiement']],
        'titres_recette' => [['exercice_id', 'statut'], ['prevision_recette_id'], ['tiers_id']],
        'mouvements_tresorerie' => [['compte_tresorerie_id', 'type'], ['mandat_id'], ['titre_recette_id']],
        'previsions_recette' => [['nature_id']],
    ];

    public function up(): void
    {
        foreach ($this->index as $table => $liste) {
            Schema::table($table, function (Blueprint $t) use ($table, $liste) {
                foreach ($liste as $colonnes) {
                    $t->index($colonnes, $this->nom($table, $colonnes));
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->index as $table => $liste) {
            Schema::table($table, function (Blueprint $t) use ($table, $liste) {
                foreach ($liste as $colonnes) {
                    $t->dropIndex($this->nom($table, $colonnes));
                }
            });
        }
    }

    private function nom(string $table, array $colonnes): string
    {
        return 'idx_'.$table.'_'.implode('_', $colonnes);
    }
};
