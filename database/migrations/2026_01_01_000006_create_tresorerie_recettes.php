<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titres_recette', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('prevision_recette_id')->constrained('previsions_recette');
            $table->foreignId('tiers_id')->constrained('tiers');
            $table->string('numero', 30)->unique();
            $table->date('date');
            $table->date('date_echeance')->nullable();
            $table->string('objet');
            $table->decimal('montant', 17, 2);
            $table->decimal('montant_recouvre', 17, 2)->default(0);
            $table->string('statut', 30)->default('emis')->index();
            $table->foreignId('ecriture_id')->nullable()->constrained('ecritures')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('comptes_tresorerie', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('type', 20);
            $table->string('numero', 100)->nullable();
            $table->foreignId('compte_id')->constrained('comptes');
            $table->foreignId('journal_id')->constrained('journaux');
            $table->decimal('solde_initial', 17, 2)->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('mouvements_tresorerie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_tresorerie_id')->constrained('comptes_tresorerie');
            $table->date('date')->index();
            $table->string('type', 20);
            $table->decimal('montant', 17, 2);
            $table->string('libelle');
            $table->string('mode', 20)->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('compte_id')->nullable()->constrained('comptes');
            $table->foreignId('tiers_id')->nullable()->constrained('tiers')->nullOnDelete();
            $table->foreignId('mandat_id')->nullable()->constrained('mandats')->nullOnDelete();
            $table->foreignId('titre_recette_id')->nullable()->constrained('titres_recette')->nullOnDelete();
            $table->foreignId('ecriture_id')->nullable()->constrained('ecritures')->nullOnDelete();
            $table->string('virement_id', 40)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_tresorerie');
        Schema::dropIfExists('comptes_tresorerie');
        Schema::dropIfExists('titres_recette');
    }
};
