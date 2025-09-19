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
        Schema::create('exercice_mouvement_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('exercices')->onDelete('cascade');
            $table->foreignId('coupon_ticket_id')->constrained('coupon_tickets')->onDelete('cascade');
            $table->foreignId('compagnie_petrolier_id')->constrained('compagnie_petroliers')->onDelete('cascade');
            $table->string('qte_actuel');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercice_mouvement_ticket');
    }
};
