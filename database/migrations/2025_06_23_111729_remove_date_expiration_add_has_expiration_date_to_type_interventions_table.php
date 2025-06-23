<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     * C'est ici que vous ajoutez ou modifiez des colonnes.
     */
    public function up(): void
    {
        Schema::table('type_interventions', function (Blueprint $table) {
            // 1. Supprime la colonne 'date_expiration' si elle existe
            // Il est important de vérifier son existence avant de la supprimer pour éviter des erreurs
            // si la migration est exécutée plusieurs fois ou sur une base de données sans cette colonne.
            if (Schema::hasColumn('type_interventions', 'date_expiration')) {
                $table->dropColumn('date_expiration');
            }

            // 2. Ajoute la nouvelle colonne 'has_expiration_date' comme booléen
            // Elle sera par défaut FALSE (0) pour les enregistrements existants,
            // ce qui est un bon comportement par défaut si aucune date n'était définie.
            $table->boolean('has_expiration_date')->default(false)->after('observation');
        });
    }

    /**
     * Annule les migrations.
     * C'est ici que vous annulez les changements faits dans la méthode 'up'.
     */
    public function down(): void
    {
        Schema::table('type_interventions', function (Blueprint $table) {
            // 1. Supprime la colonne 'has_expiration_date'
            // Vérifiez son existence avant de la supprimer.
            if (Schema::hasColumn('type_interventions', 'has_expiration_date')) {
                $table->dropColumn('has_expiration_date');
            }

            // 2. Ré-ajoute la colonne 'date_expiration' avec ses propriétés d'origine
            // C'est important pour pouvoir annuler la migration proprement.
            // Assurez-vous que le type et la nullabilité correspondent à votre structure précédente.
            // Si elle était nullable, elle doit l'être ici aussi.
            $table->date('date_expiration')->nullable()->after('observation');
        });
    }
};

