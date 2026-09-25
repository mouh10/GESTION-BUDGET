<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lignes de crédits de dépenses : programme/action × service × nature × source.
        Schema::create('lignes_credit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('action_id')->constrained('actions');
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('nature_id')->constrained('natures');
            $table->string('source', 20)->default('etat');
            $table->string('libelle')->nullable();
            $table->decimal('ae_initiale', 17, 2)->default(0);
            $table->decimal('cp_initial', 17, 2)->default(0);
            $table->timestamps();
            $table->unique(['exercice_id', 'action_id', 'service_id', 'nature_id', 'source'], 'ligne_credit_unique');
        });

        // Prévisions de recettes.
        Schema::create('previsions_recette', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->foreignId('nature_id')->constrained('natures');
            $table->string('libelle')->nullable();
            $table->decimal('montant_prevu', 17, 2)->default(0);
            $table->timestamps();
            $table->unique(['exercice_id', 'service_id', 'nature_id']);
        });

        // Actes de modification budgétaire (virements, transferts, ouvertures, annulations, gels).
        Schema::create('modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->string('numero', 30)->unique();
            $table->date('date');
            $table->string('type', 20);
            $table->string('reference_acte', 150)->nullable();
            $table->text('motif')->nullable();
            $table->string('statut', 20)->default('brouillon');
            $table->timestamp('approuve_le')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('modification_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modification_id')->constrained('modifications')->cascadeOnDelete();
            $table->foreignId('ligne_credit_id')->constrained('lignes_credit');
            $table->decimal('ae', 17, 2)->default(0);
            $table->decimal('cp', 17, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modification_lignes');
        Schema::dropIfExists('modifications');
        Schema::dropIfExists('previsions_recette');
        Schema::dropIfExists('lignes_credit');
    }
};
