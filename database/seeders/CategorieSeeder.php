<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\CategorieArticle;

class CategorieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['libelle_categorie_article' => "Électronique"],
            ['libelle_categorie_article' => "Informatique"],
            ['libelle_categorie_article' => "Fournitures de bureau"],
            ['libelle_categorie_article' => "Vêtements et accessoires"],
            ['libelle_categorie_article' => "Alimentaire"],
            ['libelle_categorie_article' => "Produits ménagers"],
        ];

        foreach ($categories as $categorie) {
            CategorieArticle::firstOrCreate($categorie);
        }
    }
}
