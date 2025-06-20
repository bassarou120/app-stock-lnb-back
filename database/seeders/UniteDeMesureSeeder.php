<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\UniteDeMesure;

class UniteDeMesureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste des rôles à insérer
        $unites = ['Litre (L)', 'Kilogramme (kg)', 'Unité (u)', 'Pièce (pcs)', 'Douzaine '];

        foreach ($unites as $unite) {
            UniteDeMesure::firstOrCreate([
                'libelle' => $unite
            ]);
        }

        $this->command->info('Les unités ont été ajoutés avec succès !');
    }
}
