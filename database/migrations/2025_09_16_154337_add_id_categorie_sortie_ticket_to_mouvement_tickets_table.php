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
            // Ajoute la colonne `id_categorie_sortie_ticket` comme clé étrangère
            $table->foreignId('id_categorie_sortie_ticket')
                  ->nullable()
                  ->constrained('categorie_sortie_tickets')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_tickets', function (Blueprint $table) {
            // Retire la clé étrangère d'abord
            $table->dropConstrainedForeignId('id_categorie_sortie_ticket');
        });
    }
};
