<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\StatusImmo;


class StatusImmoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $StatusImmos = [
            ['libelle_status_immo' => "En magasin"],
            ['libelle_status_immo' => "En service"],
            ['libelle_status_immo' => "Sortie de patrimoine"],
            ['libelle_status_immo' => "Au parc"],
            ['libelle_status_immo' => "Au pool"],
        ];

        foreach ($StatusImmos as $StatusImmo) {
            StatusImmo::firstOrCreate($StatusImmo);
        }
    }
}
