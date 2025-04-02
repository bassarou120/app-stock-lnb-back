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
}
