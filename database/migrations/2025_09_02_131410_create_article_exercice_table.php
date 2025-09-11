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
        Schema::create('article_exercice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_article')
                  ->constrained('articles')
                  ->onDelete('cascade');

            // Clé étrangère pour relier la table des exercices
            $table->foreignId('id_exercice')
                  ->constrained('exercices')
                  ->onDelete('cascade');

            $table->integer('stock_debut_exercice')->nullable();
            $table->integer('stock_fin_exercice')->nullable();
            $table->decimal('cmp_debut_exercice', 10, 2)->nullable();
            $table->decimal('cmp_fin_exercice', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_exercice');
    }
};
