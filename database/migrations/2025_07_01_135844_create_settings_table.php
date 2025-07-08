<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     * Crée la table 'settings' avec les colonnes 'id', 'key', 'value', 'type' et timestamps.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); // Colonne ID auto-incrémentée (primary key)
            $table->string('key')->unique(); // Clé unique du paramètre (ex: 'company_name', 'logo_url')
            $table->text('value')->nullable(); // Valeur du paramètre (URL, texte, etc.). Nullable pour flexibilité.
            $table->string('type')->nullable(); // Type du paramètre (ex: 'text', 'image_url', 'base64_image')
            $table->timestamps(); // Ajoute les colonnes 'created_at' et 'updated_at'
        });
    }

    /**
     * Annule les migrations.
     * Supprime la table 'settings' si elle existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
