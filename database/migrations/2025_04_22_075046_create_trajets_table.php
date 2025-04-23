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
        Schema::create('trajets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('MouvementTicket_id')->constrained('mouvement_tickets')->onDelete('cascade');
            $table->foreignId('commune_depart')->constrained('communes')->onDelete('cascade');
            $table->foreignId('commune_arriver')->constrained('communes')->onDelete('cascade');
            $table->boolean('trajet_aller_retour')->default(false);
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trajets');
    }
};
