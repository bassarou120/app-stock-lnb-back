<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\TypeAffectation;


class TypeAffectationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typeAffectations = [
            ['libelle_type_affectation' => "Affectation d'Article"],
            ['libelle_type_affectation' => "Affectation d'Immobilisation"],
        ];

        foreach ($typeAffectations as $type) {
            TypeAffectation::firstOrCreate($type);
        }
    }
}
