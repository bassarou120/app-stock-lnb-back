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
            $table->foreignId('commune_depart')->nullable()->constrained('communes')->onDelete('cascade');
            $table->foreignId('commune_arriver')->nullable()->constrained('communes')->onDelete('cascade');
            $table->boolean('trajet_aller_retour')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_tickets', function (Blueprint $table) {
            $table->dropForeign(['commune_depart']);
            $table->dropForeign(['commune_arriver']);
            $table->dropColumn(['commune_depart', 'commune_arriver','trajet_aller_retour']);
        });
    }
};
