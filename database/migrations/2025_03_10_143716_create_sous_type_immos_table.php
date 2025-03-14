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
        Schema::create('sous_type_immos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_type_immo')->constrained('type_immos')->onDelete('cascade');
            $table->string('libelle', 255);
            $table->integer('compte');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sous_type_immos');
    }
};
