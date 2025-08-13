<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Marque;


class MarqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marques = [
            ['libelle' => 'Toyota'],
            ['libelle' => 'Honda'],
            ['libelle' => 'Nissan'],
            ['libelle' => 'Ford'],
            ['libelle' => 'Chevrolet'],
            ['libelle' => 'Hyundai'],
            ['libelle' => 'Kia'],
            ['libelle' => 'Volkswagen'],
            ['libelle' => 'Peugeot'],
            ['libelle' => 'Renault'],
            ['libelle' => 'Mercedes-Benz'],
            ['libelle' => 'BMW'],
            ['libelle' => 'Audi'],
            ['libelle' => 'Mazda'],
            ['libelle' => 'Mitsubishi'],
            ['libelle' => 'Isuzu'],
            ['libelle' => 'Land Rover'],
            ['libelle' => 'Suzuki'],
            ['libelle' => 'Subaru'],
            ['libelle' => 'Fiat'],

            ['libelle' => 'CAMION IVECO'],
            ['libelle' => 'HONDA CR_V'],
            ['libelle' => 'HYUNDAI ACCENT'],
            ['libelle' => 'TOYOTA DOUBLE CABINE'],
        ];

        foreach ($marques as $marque) {
            Marque::firstOrCreate($marque);
        }
    }
}
