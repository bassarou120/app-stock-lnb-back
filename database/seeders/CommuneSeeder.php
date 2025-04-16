<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Commune;


class CommuneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $communes = [
            ['libelle_commune' => 'Alibori'],
            ['libelle_commune' => 'Atacora'],
            ['libelle_commune' => 'Atlantique'],
            ['libelle_commune' => 'Borgou'],
            ['libelle_commune' => 'Collines'],
            ['libelle_commune' => 'Donga'],
            ['libelle_commune' => 'Couffo'],
            ['libelle_commune' => 'Littoral'],
            ['libelle_commune' => 'Mono'],
            ['libelle_commune' => 'Ouémé'],
            ['libelle_commune' => 'Plateau'],
            ['libelle_commune' => 'Zou'],
        ];

        foreach ($communes as $commune) {
            Commune::firstOrCreate($commune);
        }
    }
}
