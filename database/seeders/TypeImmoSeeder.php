<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\TypeImmo;


class TypeImmoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['libelle_typeImmo' => "Mobilier de Bureau", 'compte' => 2444],
            ['libelle_typeImmo' => "Matériel Informatique", 'compte' => 2455],
            ['libelle_typeImmo' => "Véhicules", 'compte' => 2466],
            ['libelle_typeImmo' => "Terrains et Bâtiments", 'compte' => 2477],
        ];

        foreach ($types as $type) {
            TypeImmo::firstOrCreate(['libelle_typeImmo' => $type['libelle_typeImmo']], $type);
        }
    }
}
