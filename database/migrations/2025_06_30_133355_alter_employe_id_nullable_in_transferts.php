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
        Schema::table('transferts', function (Blueprint $table) {
            // Supprimer la contrainte existante
            $table->dropForeign(['employe_id']);

            // Rendre la colonne nullable
            $table->foreignId('employe_id')
                ->nullable()
                ->change();

            // Réappliquer la contrainte
            $table->foreign('employe_id')
                ->references('id')
                ->on('employes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->dropForeign(['employe_id']);

            $table->foreignId('employe_id')
                ->nullable(false)
                ->change();

            $table->foreign('employe_id')
                ->references('id')
                ->on('employes')
                ->onDelete('cascade');
        });
    }
};
