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
        Schema::table('groupe_type_immos', function (Blueprint $table) {
            // D'abord on doit supprimer la contrainte de clé étrangère (si elle existe)
            $table->dropForeign(['id_sous_type_immo']);

            // Ensuite on supprime la colonne
            $table->dropColumn('id_sous_type_immo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groupe_type_immos', function (Blueprint $table) {
           // On restaure la colonne et la contrainte
           $table->foreignId('id_sous_type_immo')
           ->constrained('sous_type_immos')
           ->onDelete('cascade');
        });
    }
};
