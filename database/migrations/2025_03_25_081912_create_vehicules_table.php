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
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marque_id')->constrained('marques')->onDelete('cascade');
            $table->foreignId('modele_id')->constrained('modeles')->onDelete('cascade');
            $table->string('immatriculation');
            $table->string('numero_chassis')->nullable();
            $table->integer('kilometrage');
            $table->date('date_mise_en_service');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};
