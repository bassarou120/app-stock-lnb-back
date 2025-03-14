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
        Schema::create('type_interventions', function (Blueprint $table) {
            $table->id();
            $table->string('libelle_type_intervention');
            $table->boolean('applicable_seul_vehicule')->default(false);
            $table->string('observation', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_interventions');
    }
};
