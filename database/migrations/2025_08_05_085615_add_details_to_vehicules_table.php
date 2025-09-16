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
        Schema::table('vehicules', function (Blueprint $table) {
            $table->string('puissance', 100)->nullable()->after('date_mise_en_service');
            $table->integer('places_assises')->nullable()->after('puissance');
            $table->string('energie', 50)->nullable()->after('places_assises');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicules', function (Blueprint $table) {
            $table->dropColumn(['puissance', 'places_assises', 'energie']);
        });
    }
};
