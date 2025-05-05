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
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            $table->foreignId('id_employe')->nullable()->constrained('employes')->onDelete('cascade');
            $table->foreignId('bureau_id')->nullable()->constrained('bureaus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            $table->dropForeign(['id_employe']);
            $table->dropColumn('id_employe');

            $table->dropForeign(['bureau_id']);
            $table->dropColumn('bureau_id');
        });
    }
};
