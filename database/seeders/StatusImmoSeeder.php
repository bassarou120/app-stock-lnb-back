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
            // ['libelle_status_immo' => "En panne"],
        ];

        foreach ($StatusImmos as $StatusImmo) {
            StatusImmo::firstOrCreate($StatusImmo);
        }
    }
}