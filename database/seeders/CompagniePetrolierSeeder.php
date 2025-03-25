<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\CompagniePetrolier;


class CompagniePetrolierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $compagnies = [
            [
                'libelle' => 'SONACOP',
                'adresse' => 'Carrefour Zongo, Cotonou'
            ],
            [
                'libelle' => 'OLA Energy Bénin',
                'adresse' => 'Boulevard de la Marina, Cotonou'
            ],
            [
                'libelle' => 'TOTAL Bénin',
                'adresse' => 'Avenue Steinmetz, Cotonou'
            ],
            [
                'libelle' => 'Petrolex Bénin',
                'adresse' => 'Zone portuaire, Cotonou'
            ],
            [
                'libelle' => 'ORYX Bénin',
                'adresse' => 'Route de Porto-Novo, Cotonou'
            ],
            [
                'libelle' => 'MRS Oil Bénin',
                'adresse' => 'Akpakpa, Cotonou'
            ],
            [
                'libelle' => 'Puma Energy Bénin',
                'adresse' => 'Zone industrielle, Sèmè-Podji'
            ],
            [
                'libelle' => 'JNP Bénin',
                'adresse' => 'Zone industrielle Akpakpa, Cotonou'
            ],
        ];

        foreach ($compagnies as $compagnie) {
            CompagniePetrolier::firstOrCreate([
                'libelle' => $compagnie['libelle'],
            ], [
                'adresse' => $compagnie['adresse'],
            ]);
        }
    }
}
