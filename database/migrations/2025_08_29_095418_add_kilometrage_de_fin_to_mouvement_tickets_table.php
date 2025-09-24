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
        Schema::table('mouvement_tickets', function (Blueprint $table) {
            $table->integer('kilometrage_de_fin')->nullable()->after('kilometrage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_tickets', function (Blueprint $table) {
            $table->dropColumn('kilometrage_de_fin');
        });
    }
};
