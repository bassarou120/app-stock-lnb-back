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
        Schema::table('intervention_vehicules', function (Blueprint $table) {
            $table->date('date_expiration')->nullable()->after('type_intervention_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_vehicules', function (Blueprint $table) {
            $table->dropColumn('date_expiration');
        });
    }
};
