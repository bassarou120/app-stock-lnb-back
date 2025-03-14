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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            // Clé étrangère vers le rôle
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            // Clé étrangère vers le module
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            // Clé étrangère vers la fonctionnalité
            $table->foreignId('fonctionnalite_id')->constrained('fonctionnalites')->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
