<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecritures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('journal_id')->constrained('journaux');
            $table->string('numero_piece', 30)->unique();
            $table->date('date')->index();
            $table->string('libelle');
            $table->string('reference', 100)->nullable();
            $table->string('statut', 20)->default('brouillon')->index();
            $table->nullableMorphs('source');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lignes_ecriture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecriture_id')->constrained('ecritures')->cascadeOnDelete();
            $table->foreignId('compte_id')->constrained('comptes');
            $table->foreignId('tiers_id')->nullable()->constrained('tiers')->nullOnDelete();
            $table->string('libelle')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_ecriture');
        Schema::dropIfExists('ecritures');
    }
};
