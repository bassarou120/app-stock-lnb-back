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
        Schema::create('mouvement_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_type_mouvement')->nullable()->constrained('type_mouvements')->onDelete('cascade');
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->onDelete('cascade');
            $table->foreignId('compagnie_petrolier_id')->nullable()->constrained('compagnie_petroliers')->onDelete('cascade');
            $table->foreignId('coupon_ticket_id')->nullable()->constrained('coupon_tickets')->onDelete('cascade');
            $table->integer('kilometrage')->nullable();
            $table->foreignId('employe_id')->nullable()->constrained('employes')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->string('objet')->nullable();
            $table->string('description')->nullable();
            $table->integer('qte')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvement_tickets');
    }
};
