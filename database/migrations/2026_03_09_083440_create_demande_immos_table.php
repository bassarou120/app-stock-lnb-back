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
        Schema::create('demande_immos', function (Blueprint $table) {
            $table->id();
            $table->string('ref_demande')->nullable();
            $table->foreignId('id_employe')->nullable()->constrained('employes')->onDelete('cascade');
            $table->foreignId('id_traiteur')->nullable()->constrained('employes')->onDelete('cascade');
            $table->foreignId('id_groupe_type_immo')->nullable()->constrained('groupe_type_immos')->onDelete('cascade');
            $table->foreignId('id_immo')->nullable()->constrained('immobilisations')->onDelete('cascade');
            $table->date('date_demande')->nullable();
            $table->string('status')->nullable();
            $table->string('url_fiche')->nullable();
            $table->foreignId('id_exercice')->nullable()->constrained('exercices')->onDelete('cascade');
            $table->string('mRequest')->nullable();
            $table->boolean('isdeleted')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_immos');
    }
};