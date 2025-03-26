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
            // Supprimer la contrainte de clé étrangère actuelle
            $table->dropForeign(['bureau_id']);

            // Modifier la colonne pour qu'elle soit nullable
            $table->foreignId('bureau_id')
                ->nullable()
                ->change();

            // Réappliquer la contrainte de clé étrangère avec nullable
            $table->foreign('bureau_id')
                ->references('id')
                ->on('bureaus')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immobilisations', function (Blueprint $table) {
            $table->dropForeign(['bureau_id']);

            $table->foreignId('bureau_id')
                ->nullable(false)
                ->change();

            $table->foreign('bureau_id')
                ->references('id')
                ->on('bureaus')
                ->onDelete('cascade');
        });
    }
};
