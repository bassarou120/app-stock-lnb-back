<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\GroupeTypeImmo;


class GroupeTypeImmoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groupes = [
            ['libelle' => "Table Parakou", 'compte' => 24440005],
            ['libelle' => "Chaise Parakou", 'compte' => 24440005],
            ['libelle' => "Banc Parakou", 'compte' => 24440005],
            ['libelle' => "Table Cotonou ", 'compte' => 24440002],
            ['libelle' => "Chaise Cotonou ", 'compte' => 24440002],
            ['libelle' => "Banc Cotonou ", 'compte' => 24440002],
        ];

        foreach ($groupes as $groupe) {
            GroupeTypeImmo::firstOrCreate(['libelle' => $groupe['libelle']], $groupe);
        }
    }
}
