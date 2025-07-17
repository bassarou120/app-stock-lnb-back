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
            ['libelle_type_intervention' => 'Visite technique', 'applicable_seul_vehicule' => true],
            ['libelle_type_intervention' => 'Vidange','applicable_seul_vehicule' => true],
            ['libelle_type_intervention' => 'Réparation','applicable_seul_vehicule' => true],
            ['libelle_type_intervention' => 'Réparation Générale des Immos','applicable_seul_vehicule' => false],
        ];

        foreach ($typesintervention as $type) {
            TypeIntervention::firstOrCreate($type);
        }
    }
}
