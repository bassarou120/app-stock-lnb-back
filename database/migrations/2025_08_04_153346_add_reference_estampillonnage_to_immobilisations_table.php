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
        Schema::table('immobilisations', function (Blueprint $table) {
            $table->string('reference_estampillonnage', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immobilisations', function (Blueprint $table) {
            $table->dropColumn('reference_estampillonnage');
        });
    }
};
