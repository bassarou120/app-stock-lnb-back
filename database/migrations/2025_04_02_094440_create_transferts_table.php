<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immo_id')->constrained('immobilisations')->onDelete('cascade');
            $table->foreignId('old_bureau_id')->nullable()->constrained('bureaus')->onDelete('cascade');
            $table->foreignId('old_employe_id')->nullable()->constrained('employes')->onDelete('cascade');
            $table->foreignId('bureau_id')->constrained('bureaus')->onDelete('cascade');
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->date('date_mouvement');
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
