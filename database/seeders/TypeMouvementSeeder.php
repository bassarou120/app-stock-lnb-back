<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\TypeMouvement;


class TypeMouvementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typesMouvement = [
            ['libelle_type_mouvement' => "Entrée de Stock"],
            ['libelle_type_mouvement' => "Sortie de Stock"],
        ];

        foreach ($typesMouvement as $type) {
            TypeMouvement::firstOrCreate($type);
        }
    }
}
