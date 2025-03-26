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
        Schema::create('immobilisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_id')->constrained('bureaus')->onDelete('cascade');
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->date('date_mouvement')->nullable();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('cascade');
            $table->string('designation')->nullable();
            $table->boolean('isVehicule')->default(false);
            $table->foreignId('vehicule_id')->constrained('vehicules')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->foreignId('id_groupe_type_immo')->constrained('groupe_type_immos')->onDelete('cascade');
            $table->foreignId('id_sous_type_immo')->constrained('sous_type_immos')->onDelete('cascade');
            $table->integer('duree_amorti')->nullable();
            $table->string('etat')->nullable();
            $table->integer('taux_ammortissement')->nullable();
            $table->integer('duree_ammortissement')->nullable();
            $table->date('date_acquisition')->nullable();
            $table->date('date_mise_en_service')->nullable();
            $table->string('observation')->nullable();
            $table->foreignId('id_status_immo')->constrained('status_immos')->onDelete('cascade');
            $table->integer('montant_ttc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immobilisations');
    }
};
