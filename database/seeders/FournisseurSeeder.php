<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Fournisseur;


class FournisseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fournisseurs = [
            [
                'nom' => 'ACOR SARL',
                'telephone' => '+229 97 00 00 01',
                'adresse' => 'Abomey-Calavi, Bénin',
            ],
            [
                'nom' => 'ADS TRANS',
                'telephone' => '+229 97 00 00 02',
                'adresse' => 'Cotonou, Bénin',
            ],
            [
                'nom' => 'APS BENIN',
                'telephone' => '+229 97 00 00 03',
                'adresse' => 'Cotonou, Bénin',
            ],
            [
                'nom' => 'BOANERGES TRANS Sarl',
                'telephone' => '+229 97 00 00 04',
                'adresse' => 'Cotonou, Bénin',
            ],
            [
                'nom' => 'BTG GROUPE',
                'telephone' => '+229 97 00 00 05',
                'adresse' => 'Cotonou, Bénin',
            ]
        ];

        foreach ($fournisseurs as $fournisseur) {
            Fournisseur::firstOrCreate($fournisseur);
        }
    }
}
