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
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immo_id')->constrained('immobilisations')->onDelete('cascade');
            $table->foreignId('type_intervention_id')->constrained('type_interventions')->onDelete('cascade');
            $table->date('date_intervention');
            $table->string('titre');
            $table->integer('cout');
            $table->string('observation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
