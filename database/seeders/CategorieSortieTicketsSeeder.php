<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategorieSortieTicket;

class CategorieSortieTicketsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['libelle' => "Dotation Agences"],
            ['libelle' => "Dotation Chef Garage"],
            ['libelle' => "Groupe Electrogène"],
            ['libelle' => "Missions"],
        ];

        foreach ($categories as $categorie) {
            CategorieSortieTicket::firstOrCreate($categorie);
        }
    }
}