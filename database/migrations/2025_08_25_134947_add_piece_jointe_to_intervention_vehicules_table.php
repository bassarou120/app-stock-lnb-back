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
        Schema::table('intervention_vehicules', function (Blueprint $table) {
            $table->string('piece_jointe')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_vehicules', function (Blueprint $table) {
            $table->dropColumn('piece_jointe');
        });
    }
};
