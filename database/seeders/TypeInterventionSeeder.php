<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\TypeIntervention;

class TypeInterventionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typesintervention = [
            ['libelle_type_intervention' => 'Visite technique'],
            ['libelle_type_intervention' => 'Vidange'],
            ['libelle_type_intervention' => 'Réparation'],
        ];

        foreach ($typesintervention as $type) {
            TypeIntervention::firstOrCreate($type);
        }
    }
}
