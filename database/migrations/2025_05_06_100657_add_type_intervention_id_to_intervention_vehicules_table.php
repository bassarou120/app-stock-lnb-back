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
            $table->foreignId('type_intervention_id')->nullable()->constrained('type_interventions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_vehicules', function (Blueprint $table) {
            $table->dropColumn('type_intervention_id');
        });
    }
};
