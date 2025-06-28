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
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            $table->foreignId('id_unite_de_mesure')->nullable()->constrained('unite_de_mesures')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            $table->dropForeign(['id_unite_de_mesure']);
            $table->dropColumn('id_unite_de_mesure');
        });
    }
};
