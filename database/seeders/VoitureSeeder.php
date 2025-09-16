<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Marque;
use App\Models\Parametrage\Modele;
use App\Models\Vehicule;
use Illuminate\Support\Str;

class VoitureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


    public function run(): void
    {
        $vehicules = [];

        $vehicules =  [

            [
                'immatriculation' => 'BP 9157 RB',
                'marque_id' => 22,
                'puissance' => '11 CV',
                'places_assises' => '5',
                'energie' => 'Essence',
                'date_mise_en_service' => '2018-12-20',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'

            ],

            [
                'immatriculation' => 'BS 2905 RB',
                'marque_id' => 21,
                'puissance' => '26 CV',
                'places_assises' => 2,
                'energie' => 'Essence',
                'date_mise_en_service' => '2019-08-12',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

            [
                'immatriculation' => 'BX 5898 RB',
                'marque_id' => 24,
                'puissance' => '12 CV',
                'places_assises' => 5,
                'energie' => 'Gas oil',
                'date_mise_en_service' => '2021-05-12',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

            [
                'immatriculation' => 'BX 5899 RB',
                'marque_id' => 24,
                'puissance' => '12 CV',
                'places_assises' => 5,
                'energie' => 'Gas oil',
                'date_mise_en_service' => '2021-05-13',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

            [
                'immatriculation' => 'BX 5903 RB',
                'marque_id' => 24,
                'puissance' => '12 CV',
                'places_assises' => 5,
                'energie' => 'Gas oil',
                'date_mise_en_service' => '2021-05-14',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],


            [
                'immatriculation' => 'BX 5904 RB',
                'marque_id' => 24,
                'puissance' => '12 CV',
                'places_assises' => 5,
                'energie' => 'Gas oil',
                'date_mise_en_service' => '2021-05-12',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],


            [
                'immatriculation' => 'BX 6153 RB',
                'marque_id' => 24,
                'puissance' => '12 CV',
                'places_assises' => 5,
                'energie' => 'Gas oil',
                'date_mise_en_service' => '2021-05-13',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

            [
                'immatriculation' => 'BY 5200 RB',
                'marque_id' => 23,
                'puissance' => '12 CV',
                'places_assises' => 7,
                'energie' => 'Essence',
                'date_mise_en_service' => '2021-09-11',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

            [
                'immatriculation' => 'BY 5171 RB',
                'marque_id' => 23,
                'puissance' => '12 CV',
                'places_assises' => 7,
                'energie' => 'Essence',
                'date_mise_en_service' => '2021-09-11',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

            [
                'immatriculation' => 'BY 5179 RB',
                'marque_id' => 23,
                'puissance' => '12 CV',
                'places_assises' => 7,
                'energie' => 'Essence',
                'date_mise_en_service' => '2021-09-11',
                'kilometrage' =>   100000,
                'numero_chassis' => '1234567890'
            ],

        ];


        for ($i = 0; $i < 8; $i++) {
            $immatriculation = strtoupper(Str::random(2)) . '-' . rand(1000, 9999) . '-' . 'BJ';
            $numero_chassis = 'BJ' . strtoupper(Str::random(14));

            $vehicules[] = [
                'immatriculation' => $immatriculation,
                'numero_chassis' => $numero_chassis,
                'kilometrage' => rand(10000, 200000),
                'date_mise_en_service' => now()->subYears(rand(1, 10))->toDateString(),
            ];
        }

        foreach ($vehicules as $vehiculeData) {
            $marque = Marque::inRandomOrder()->first();
            $modele = Modele::where('libelle_modele', 'LIKE', "$marque->libelle%")->inRandomOrder()->first();

            if (!$modele) {
                $modele = Modele::inRandomOrder()->first(); // Prendre un modèle aléatoire si aucun n'est trouvé
            }

            Vehicule::create(array_merge($vehiculeData, [
                'marque_id' => $marque->id,
                'modele_id' => $modele->id,
            ]));
        }
    }
//    public function run(): void
//    {
//        $vehicules = [];
//
//        for ($i = 0; $i < 8; $i++) {
//            $immatriculation = strtoupper(Str::random(2)) . '-' . rand(1000, 9999) . '-' . 'BJ';
//            $numero_chassis = 'BJ' . strtoupper(Str::random(14));
//
//            $vehicules[] = [
//                'immatriculation' => $immatriculation,
//                'numero_chassis' => $numero_chassis,
//                'kilometrage' => rand(10000, 200000),
//                'date_mise_en_service' => now()->subYears(rand(1, 10))->toDateString(),
//            ];
//        }
//
//        foreach ($vehicules as $vehiculeData) {
//            $marque = Marque::inRandomOrder()->first();
//            $modele = Modele::where('libelle_modele', 'LIKE', "$marque->libelle%")->inRandomOrder()->first();
//
//            if (!$modele) {
//                $modele = Modele::inRandomOrder()->first(); // Prendre un modèle aléatoire si aucun n'est trouvé
//            }
//
//            Vehicule::create(array_merge($vehiculeData, [
//                'marque_id' => $marque->id,
//                'modele_id' => $modele->id,
//            ]));
//        }
//    }
}
