<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Modele;
use Illuminate\Database\Eloquent\Model;

class ModeleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modeles = [
            ['libelle_modele' => 'Toyota Corolla'],
            ['libelle_modele' => 'Toyota Camry'],
            ['libelle_modele' => 'Toyota RAV4'],
            ['libelle_modele' => 'Toyota Hilux'],
            ['libelle_modele' => 'Toyota Yaris'],

            ['libelle_modele' => 'Honda Civic'],
            ['libelle_modele' => 'Honda Accord'],
            ['libelle_modele' => 'Honda CR-V'],
            ['libelle_modele' => 'Honda HR-V'],
            ['libelle_modele' => 'Honda Fit'],

            ['libelle_modele' => 'Nissan Altima'],
            ['libelle_modele' => 'Nissan Sentra'],
            ['libelle_modele' => 'Nissan Rogue'],
            ['libelle_modele' => 'Nissan Pathfinder'],
            ['libelle_modele' => 'Nissan Frontier'],

            ['libelle_modele' => 'Ford Focus'],
            ['libelle_modele' => 'Ford Mustang'],
            ['libelle_modele' => 'Ford F-150'],
            ['libelle_modele' => 'Ford Explorer'],
            ['libelle_modele' => 'Ford Escape'],

            ['libelle_modele' => 'Chevrolet Malibu'],
            ['libelle_modele' => 'Chevrolet Silverado'],
            ['libelle_modele' => 'Chevrolet Equinox'],
            ['libelle_modele' => 'Chevrolet Camaro'],
            ['libelle_modele' => 'Chevrolet Traverse'],

            ['libelle_modele' => 'Hyundai  Elantra'],
            ['libelle_modele' => 'Hyundai  Sonata'],
            ['libelle_modele' => 'Hyundai  Tucson'],
            ['libelle_modele' => 'Hyundai  Santa Fe'],
            ['libelle_modele' => 'Hyundai  Kona'],

            ['libelle_modele' => 'Mercedes-Benz A-Class'],
            ['libelle_modele' => 'Mercedes-Benz C-Class'],
            ['libelle_modele' => 'Mercedes-Benz E-Class'],
            ['libelle_modele' => 'Mercedes-Benz GLE'],
            ['libelle_modele' => 'Mercedes-Benz S-Class'],

            ['libelle_modele' => 'BMW Série 3 '],
            ['libelle_modele' => 'BMW Série 5'],
            ['libelle_modele' => 'BMW X3'],
            ['libelle_modele' => 'BMW X5'],
            ['libelle_modele' => 'BMW i4'],

            ['libelle_modele' => 'Peugeot 208'],
            ['libelle_modele' => 'Peugeot 308'],
            ['libelle_modele' => 'Peugeot 3008'],
            ['libelle_modele' => 'Peugeot 5008'],
            ['libelle_modele' => 'Peugeot 508'],

            ['libelle_modele' => 'Kia Rio'],
            ['libelle_modele' => 'Kia Sportage'],
            ['libelle_modele' => 'Kia Sorento'],
            ['libelle_modele' => 'Kia Optima'],
            ['libelle_modele' => 'Kia Telluride'],

            ['libelle_modele' => 'Fiat 500'],
            ['libelle_modele' => 'Fiat Panda'],
            ['libelle_modele' => 'Fiat Tipo'],
            ['libelle_modele' => 'Fiat 124 Spider'],
            ['libelle_modele' => 'Fiat Ducato'],
        ];

        foreach ($modeles as $modele) {
            Modele::firstOrCreate($modele);
        }
    }
}
