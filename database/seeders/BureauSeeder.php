<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Bureau;


class BureauSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bureaux = [
            ['libelle_bureau' => 'Bureau Direction'],
            ['libelle_bureau' => 'Bureau RH'],
            ['libelle_bureau' => 'Bureau Comptabilité'],
            ['libelle_bureau' => 'Bureau Logistique'],
            ['libelle_bureau' => 'Bureau Technique'],
            ['libelle_bureau' => 'Magasin'],
        ];

        foreach ($bureaux as $bureau) {
            Bureau::firstOrCreate($bureau);
        }
    }
}
