<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Module;


class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            ['libelle_module' => 'Gestion de Stock'],
            ['libelle_module' => 'Gestion de immobilisation'],
            ['libelle_module' => 'Gestion de parc'],
            ['libelle_module' => 'Gestion Rapport'],
            ['libelle_module' => 'Gestion des utilisateurs'],
            ['libelle_module' => 'Parametrage'],

        ];

        foreach ($modules as $module) {
            Module::firstOrCreate(['libelle_module' => $module['libelle_module']]);
        }

        $this->command->info('Les modules ont été ajoutés avec succès !');
    }
}
