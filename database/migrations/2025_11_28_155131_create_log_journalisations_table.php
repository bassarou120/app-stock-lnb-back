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
        Schema::create('log_journalisations', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('date_action')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('user_agent')->nullable();

            // ✅ Clé étrangère UUID vers users
            $table->foreignId('user_id')
                ->nullable() // Permettre la valeur NULL (pour la connexion échouée et on delete set null)
                ->constrained('users') // Assurez-vous que c'est bien le nom de votre table utilisateurs
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_journalisations');
    }
};
