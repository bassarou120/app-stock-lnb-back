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
            $table->foreignId('exercice_id')
              ->nullable()
              ->constrained('exercices')
              ->onDelete('cascade')
              ->after('coupon_ticket_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_tickets', function (Blueprint $table) {
            $table->dropForeign(['exercice_id']);
            $table->dropColumn('exercice_id');
        });
    }
};
