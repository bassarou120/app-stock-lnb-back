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
        Schema::create('sortie_patrimoines', function (Blueprint $table) {
            $table->id();
            $table->string('code_immo', 50); 
            $table->string('designation_immo', 50);
            $table->string('type_immo', 50);
            $table->string('valeur', 50);
            $table->date('date_sortie');
            $table->boolean('isdeleted')->default(false);
            $table->foreignId('exercice_id')
              ->constrained('exercices') // Assurez-vous que votre table d'exercices s'appelle bien 'exercices'
              ->onDelete('cascade');
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sortie_patrimoines');
    }
};
