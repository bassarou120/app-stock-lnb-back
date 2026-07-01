<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected array $excludedTables = [
        'tenants',
        'migrations',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function up(): void
    {
        // 1. Ajouter la colonne tenant_id (nullable pour l'instant) + FK
        $tables = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
        ");

        foreach ($tables as $table) {
            $tableName = $table->tablename;

            if (in_array($tableName, $this->excludedTables)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->constrained('tenants')
                        ->cascadeOnDelete();
                });
            }
        }

        // 2. Créer un tenant par défaut pour accueillir les données existantes
        $defaultTenantId = DB::table('tenants')->insertGetId([
            'name'       => 'Entreprise par défaut',
            'slug'       => 'default',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Backfill : assigner toutes les lignes existantes à ce tenant par défaut
        foreach ($tables as $table) {
            $tableName = $table->tablename;

            if (in_array($tableName, $this->excludedTables)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'tenant_id')) {
                DB::table($tableName)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $defaultTenantId]);
            }
        }
    }

    public function down(): void
    {
        $tables = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
        ");

        foreach ($tables as $table) {
            $tableName = $table->tablename;

            if (in_array($tableName, $this->excludedTables)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }

        DB::table('tenants')->where('slug', 'default')->delete();
    }
};