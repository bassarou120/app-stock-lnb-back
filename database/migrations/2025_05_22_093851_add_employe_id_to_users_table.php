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
        Schema::table('users', function (Blueprint $table) {
            // S'il existe déjà une colonne 'employe_id' (par exemple, si tu as déjà fait une migration similaire)
            // alors ajoute une condition pour éviter une erreur
            if (!Schema::hasColumn('users', 'employe_id')) {
                $table->unsignedBigInteger('employe_id')->nullable()->unique(); // Laisser nullable si un user peut ne pas avoir d'employe_id initialement
                $table->foreign('employe_id')
                      ->references('id')
                      ->on('employes') // C'est le nom de ta table d'employés
                      ->onDelete('set null'); // Ou 'cascade'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'employe_id')) {
                $table->dropForeign(['employe_id']);
                $table->dropColumn('employe_id');
            }
        });
    }
};