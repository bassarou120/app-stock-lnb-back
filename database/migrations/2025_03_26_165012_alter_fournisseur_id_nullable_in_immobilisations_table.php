<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('immobilisations', function (Blueprint $table) {
            // Supprimer la contrainte existante
            $table->dropForeign(['fournisseur_id']);

            // Rendre la colonne nullable
            $table->foreignId('fournisseur_id')
                ->nullable()
                ->change();

            // Réappliquer la contrainte
            $table->foreign('fournisseur_id')
                ->references('id')
                ->on('fournisseurs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immobilisations', function (Blueprint $table) {
            $table->dropForeign(['fournisseur_id']);

            $table->foreignId('fournisseur_id')
                ->nullable(false)
                ->change();

            $table->foreign('fournisseur_id')
                ->references('id')
                ->on('fournisseurs')
                ->onDelete('cascade');
        });
    }
};
