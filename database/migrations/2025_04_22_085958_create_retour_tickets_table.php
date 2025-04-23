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
        Schema::create('retour_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mouvementTicket_id')->nullable()->constrained('mouvement_tickets')->onDelete('cascade');
            $table->foreignId('coupon_ticket_id')->nullable()->constrained('coupon_tickets')->onDelete('cascade');
            $table->foreignId('compagnie_petrolier_id')->nullable()->constrained('compagnie_petroliers')->onDelete('cascade');
            $table->integer('qte')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retour_tickets');
    }
};
