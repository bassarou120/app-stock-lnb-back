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
        Schema::table('stock_tickets', function (Blueprint $table) {
            $table->foreignId('compagnie_petrolier_id')
                  ->nullable()
                  ->constrained('compagnie_petroliers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_tickets', function (Blueprint $table) {
            $table->dropForeign(['compagnie_petrolier_id']);
            $table->dropColumn('compagnie_petrolier_id');
        });
    }
};
