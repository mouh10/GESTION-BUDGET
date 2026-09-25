<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaîne de la dépense : marché → engagement → liquidation → mandat (ordonnancement) → paiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->string('numero', 50)->unique();
            $table->string('objet');
            $table->foreignId('tiers_id')->constrained('tiers');
            $table->string('type', 30);
            $table->string('mode_passation', 30);
            $table->decimal('montant', 17, 2);
            $table->date('date_signature')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('en_cours');
            $table->foreignId('ligne_credit_id')->nullable()->constrained('lignes_credit')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->string('numero', 30)->unique();
            $table->date('date');
            $table->foreignId('ligne_credit_id')->constrained('lignes_credit');
            $table->foreignId('tiers_id')->constrained('tiers');
            $table->foreignId('marche_id')->nullable()->constrained('marches')->nullOnDelete();
            $table->string('type', 30);
            $table->string('objet');
            $table->decimal('montant', 17, 2);
            $table->string('statut', 20)->default('brouillon')->index();
            $table->text('motif_rejet')->nullable();
            $table->timestamp('soumis_le')->nullable();
            $table->timestamp('vise_le')->nullable();
            $table->foreignId('viseur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained('engagements');
            $table->string('numero', 30)->unique();
            $table->date('date');
            $table->string('reference_facture', 100)->nullable();
            $table->date('date_service_fait')->nullable();
            $table->decimal('montant', 17, 2);
            $table->text('observations')->nullable();
            $table->string('statut', 20)->default('validee');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mandats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_id')->constrained('liquidations');
            $table->string('numero', 30)->unique();
            $table->date('date');
            $table->decimal('montant', 17, 2);
            $table->string('statut', 20)->default('emis')->index();
            $table->text('motif_rejet')->nullable();
            $table->timestamp('pris_en_charge_le')->nullable();
            $table->date('date_paiement')->nullable();
            $table->foreignId('ecriture_id')->nullable()->constrained('ecritures')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandats');
        Schema::dropIfExists('liquidations');
        Schema::dropIfExists('engagements');
        Schema::dropIfExists('marches');
    }
};
