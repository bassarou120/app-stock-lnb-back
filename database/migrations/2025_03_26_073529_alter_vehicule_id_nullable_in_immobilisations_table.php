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
            // On doit d'abord supprimer la contrainte de clé étrangère existante
            $table->dropForeign(['vehicule_id']);

            // Puis modifier la colonne pour qu'elle soit nullable
            $table->foreignId('vehicule_id')
                ->nullable()
                ->change();

            // Et enfin, on rajoute à nouveau la contrainte avec nullable
            $table->foreign('vehicule_id')
                ->references('id')
                ->on('vehicules')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immobilisations', function (Blueprint $table) {
            Schema::table('immobilisations', function (Blueprint $table) {
                $table->dropForeign(['vehicule_id']);
                $table->foreignId('vehicule_id')
                    ->nullable(false)
                    ->change();

                $table->foreign('vehicule_id')
                    ->references('id')
                    ->on('vehicules')
                    ->onDelete('cascade');
            });
        });
    }
};
