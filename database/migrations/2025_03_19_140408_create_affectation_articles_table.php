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
        Schema::create('affectation_articles', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->foreignId('id_article')->constrained('articles')->onDelete('cascade');
            $table->foreignId('id_type_affectation')->constrained('type_affectations')->onDelete('cascade');
            $table->foreignId('id_bureau')->constrained('bureaus')->onDelete('cascade');
            $table->foreignId('id_employe')->constrained('employes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_articles');
    }
};
