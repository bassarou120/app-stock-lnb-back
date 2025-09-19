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
        Schema::table('mouvement_tickets', function (Blueprint $table) {
            $table->foreignId('exercice_id')
                ->nullable() // ✅ important si la table contient déjà des données
                ->constrained('exercices')
                ->onDelete('cascade')
                ->after('id'); // tu peux changer l'ordre si nécessaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_tickets', function (Blueprint $table) {
            $table->dropForeign(['exercice_id']);
            $table->dropColumn('exercice_id');
        });
    }
};
