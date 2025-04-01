<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parametrage\TypeImmo;
use App\Models\Parametrage\SousTypeImmo;

class SousTypeImmoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer l'ID du type "Mobilier de Bureau"
        $typeMobilier = TypeImmo::where('libelle_typeImmo', 'Mobilier de Bureau')->first();

        if ($typeMobilier) {
            $sousTypes = [
                ['libelle' => "Mobilier de Bureau Parakou", 'compte' => 24440005, 'id_type_immo' => $typeMobilier->id],
                ['libelle' => "Mobilier de Bureau Cotonou", 'compte' => 24440002, 'id_type_immo' => $typeMobilier->id],
            ];

            foreach ($sousTypes as $sousType) {
                SousTypeImmo::firstOrCreate(
                    ['libelle' => $sousType['libelle']],
                    $sousType
                );
            }
        }
    }
}
