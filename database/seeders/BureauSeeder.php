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
            ['libelle_bureau' => 'Bureau CAR Atacora AGENCE REGIONALE ATACORA AGENCE REGIONALE ATACORA'],
            ['libelle_bureau' => 'Bureau CAR Ouémé AGENCE REGIONALE OUEME AGENCE REGIONALE OUEME'],
            ['libelle_bureau' => 'Bureau Collaborateur DCM/Magasin Houeyi HOUEYIHO HOUEYIHO'],
            ['libelle_bureau' => 'DIRECTION GENERALE/Bureau Directeur Général DIRECTION GENERALE 1ER ETAGE'],
            ['libelle_bureau' => 'COURS AGENCE REGIONALE ZOU AGENCE REGIONALE ZOU'],
            ['libelle_bureau' => 'Cours de l\'Agence Lokossa AGENCE REGIONALE MONO AGENCE REGIONALE MONO'],
            ['libelle_bureau' => 'Immobilisations à relocaliser 2019 DIRECTION GENERALE DIRECTION GENERALE'],
            ['libelle_bureau' => 'Bureau Chef Cellule Juridique AGENCE REGIONALE ATLANTIQUE AGENCE REGIONALE ATLANTIQUE'],
            ['libelle_bureau' => 'Immobilisations à relocaliser 2019 DIRECTION GENERALE DIRECTION GENERALE'],
            ['libelle_bureau' => 'Bureau Chef Garage DIRECTION GENERALE REZ-DE-CHAUSSEE'],
            ['libelle_bureau' => 'IMMOBILISATION A RELOCALISER 2023 DIRECTION GENERALE DIRECTION GENERALE'],
            ['libelle_bureau' => 'Bureau Chef Poste POSTE DE VENTE DE DJOUGOU POSTE DE VENTE DJOUGOU'],
            ['libelle_bureau' => 'Bureau CAR Atacora AGENCE REGIONALE ATACORA AGENCE REGIONALE ATACORA'],
            ['libelle_bureau' => 'Bureau CAR Ouémé AGENCE REGIONALE OUEME AGENCE REGIONALE OUEME'],
            ['libelle_bureau' => 'Parc Auto  Porto novo AGENCE REGIONALE OUEME AGENCE REGIONALE OUEME'],
            ['libelle_bureau' => 'Poste de Vente Pobè POSTE DE VENTE DE POBE POSTE DE VENTE DE POBE'],
            ['libelle_bureau' => 'Chef Poste d\'Allada POSTE DE VENTE DE ALLADA POSTE DE VENTE DE ALLADA'],
            ['libelle_bureau' => 'Bureau du Chef Poste de Vente Nikki POSTE DE VENTE DE NIKKI POSTE DE VENTE KANDI'],
            ['libelle_bureau' => 'Bureau CAR Akpakpa AGENCE REGIONALE ATLANTIQUE AGENCE REGIONALE ATLANTIQUE'],
            ['libelle_bureau' => 'Poste de Vente Hillacondji AGENCE REGIONALE MONO AGENCE REGIONALE MONO'],
            ['libelle_bureau' => 'Bureau CAR Zou AGENCE REGIONALE ZOU AGENCE REGIONALE ZOU'],
            ['libelle_bureau' => 'Poste de Vente Savalou POSTE DE VENTE DE SAVALOU POSTE DE VENTE SAVALOU'],
            ['libelle_bureau' => 'Bureau CAR Mono AGENCE REGIONALE MONO AGENCE REGIONALE MONO'],
            ['libelle_bureau' => 'Bureau Chef Poste de Vente Malanville POSTE DE VENTE DE MALANVILLE POSTE DE VENTE DE MALANVILLE'],
            ['libelle_bureau' => 'Bureau Chef Poste de Vente Kandi POSTE DE VENTE KANDI POSTE DE VENTE KANDI'],
            ['libelle_bureau' => 'Bureau Chef Poste de Vente Calavi POSTE DE VENTE CALAVI POSTE DE VENTE CALAVI'],
            ['libelle_bureau' => 'Guérite Poste 247 DIRECTION GENERALE REZ-DE-CHAUSSEE'],
            ['libelle_bureau' => 'Magasin'],
        ];

        foreach ($bureaux as $bureau) {
            Bureau::firstOrCreate($bureau);
        }
    }
}