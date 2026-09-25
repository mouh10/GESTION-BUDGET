<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercices', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 100);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('cloture')->default(false);
            $table->timestamps();
        });

        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->string('libelle');
            $table->unsignedTinyInteger('classe')->index();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('journaux', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('libelle', 100);
            $table->string('type', 30);
            $table->timestamps();
        });

        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('code', 30)->unique();
            $table->string('nom');
            $table->string('ninea', 50)->nullable();
            $table->string('rib', 100)->nullable();
            $table->string('adresse')->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('compte_id')->constrained('comptes');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
        Schema::dropIfExists('journaux');
        Schema::dropIfExists('comptes');
        Schema::dropIfExists('exercices');
    }
};
