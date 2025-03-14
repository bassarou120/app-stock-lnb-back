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
        Schema::create('compagnie_petroliers', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');  // Le libellé de la compagnie pétrolière
            $table->string('adresse');  // L'adresse de la compagnie pétrolière
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compagnie_petroliers');
    }
};
