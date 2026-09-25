<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomenclature budgétaire : classification administrative (services),
 * programmatique (programmes, actions) et économique (natures).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle');
            $table->string('responsable')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle');
            $table->string('responsable')->nullable();
            $table->text('objectif')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained('programmes')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('libelle');
            $table->timestamps();
            $table->unique(['programme_id', 'code']);
        });

        Schema::create('natures', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->index(); // depense | recette
            $table->string('code', 20)->unique();
            $table->string('libelle');
            $table->unsignedTinyInteger('titre'); // titre (dépenses) ou catégorie (recettes)
            $table->foreignId('compte_id')->nullable()->constrained('comptes')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('natures');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('services');
    }
};
