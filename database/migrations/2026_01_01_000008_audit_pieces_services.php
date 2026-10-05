<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit, pièces jointes, restriction des utilisateurs par service
 * et changement de mot de passe obligatoire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30)->index();
            $table->string('sujet_type', 40)->nullable();
            $table->unsignedBigInteger('sujet_id')->nullable();
            $table->string('sujet_libelle')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['sujet_type', 'sujet_id']);
        });

        Schema::create('pieces_jointes', function (Blueprint $table) {
            $table->id();
            $table->string('objet_type', 40);
            $table->unsignedBigInteger('objet_id');
            $table->string('categorie', 30)->default('autre');
            $table->string('nom');
            $table->string('chemin');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('taille')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['objet_type', 'objet_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('role')->constrained('services')->nullOnDelete();
            $table->boolean('doit_changer_mdp')->default(false)->after('actif');
            $table->timestamp('derniere_connexion')->nullable()->after('doit_changer_mdp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn(['doit_changer_mdp', 'derniere_connexion']);
        });
        Schema::dropIfExists('pieces_jointes');
        Schema::dropIfExists('journal_audit');
    }
};
