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
            Schema::table('mouvement_stocks', function (Blueprint $table) {
                // Modifier la colonne description pour qu'elle accepte les valeurs nulles
                $table->string('description')->nullable()->change();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            // Revert la colonne description à son état non nullable
            $table->string('description')->nullable(false)->change();
        });
    }
};
