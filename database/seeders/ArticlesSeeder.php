<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Article;
use Illuminate\Support\Facades\DB;


class ArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les ID des catégories
        $categories = DB::table('categorie_articles')->pluck('id', 'libelle_categorie_article');
        // Liste des articles à insérer
        $articles = [
            ['id_cat' => $categories['Électronique'], 'libelle' => 'Smartphone Samsung Galaxy S24', 'description' => 'Un smartphone haut de gamme avec écran AMOLED et 5G.', 'code_article'=>'ART-20240526-1'],
            ['id_cat' => $categories['Électronique'], 'libelle' => 'Téléviseur OLED LG 55 pouces', 'description' => 'TV 4K Ultra HD avec technologie OLED pour des couleurs vives.', 'code_article'=>'ART-20240526-2'],
            ['id_cat' => $categories['Électronique'], 'libelle' => 'Écouteurs sans fil Apple AirPods Pro', 'description' => 'Écouteurs Bluetooth avec réduction de bruit active.', 'code_article'=>'ART-20240526-3'],

            ['id_cat' => $categories['Informatique'], 'libelle' => 'Ordinateur portable Dell XPS 15', 'description' => 'PC portable performant avec processeur Intel Core i7.', 'code_article'=>'ART-20240526-4'],
            ['id_cat' => $categories['Informatique'], 'libelle' => 'Disque dur externe Seagate 2 To', 'description' => 'Stockage externe USB 3.0 rapide et sécurisé.', 'code_article'=>'ART-20240526-5'],
            ['id_cat' => $categories['Informatique'], 'libelle' => 'Souris sans fil Razer Viper Ultimate', 'description' => 'Souris gaming avec capteur optique haute précision.', 'code_article'=>'ART-20240526-6'],

            ['id_cat' => $categories['Fournitures de bureau'], 'libelle' => 'Ramettes de papier A4 80g', 'description' => 'Papier blanc premium pour impression et photocopie.', 'code_article'=>'ART-20240526-7'],
            ['id_cat' => $categories['Fournitures de bureau'], 'libelle' => 'Stylo bille Bic noir (pack de 12)', 'description' => 'Stylos à encre fluide pour une écriture précise.','code_article'=>'ART-20240526-8'],
            ['id_cat' => $categories['Fournitures de bureau'], 'libelle' => 'Agrafeuse métallique avec recharge', 'description' => 'Agrafeuse robuste idéale pour le bureau.', 'code_article'=>'ART-20240526-9'],

            ['id_cat' => $categories['Vêtements et accessoires'], 'libelle' => 'Jean slim Levi’s 511', 'description' => 'Jean tendance en coton stretch confortable.', 'code_article'=>'ART-20240526-10'],
            ['id_cat' => $categories['Vêtements et accessoires'], 'libelle' => 'Montre analogique Casio Vintage', 'description' => 'Montre classique avec affichage analogique.', 'code_article'=>'ART-20240526-11'],
            ['id_cat' => $categories['Vêtements et accessoires'], 'libelle' => 'Basket Nike Air Force 1', 'description' => 'Baskets iconiques en cuir blanc.', 'code_article'=>'ART-20240526-12'],

            ['id_cat' => $categories['Alimentaire'], 'libelle' => 'Riz parfumé 5 kg', 'description' => 'Riz de qualité supérieure à grains longs.', 'code_article'=>'ART-20240526-13'],
            ['id_cat' => $categories['Alimentaire'], 'libelle' => 'Boîte de lait en poudre Nido 900g', 'description' => 'Lait en poudre enrichi en vitamines et minéraux.', 'code_article'=>'ART-20240526-14'],
            ['id_cat' => $categories['Alimentaire'], 'libelle' => 'Jus d’orange Tropicana 1L', 'description' => 'Jus d’orange 100% pur sans conservateurs.', 'code_article'=>'ART-20240526-15'],

            ['id_cat' => $categories['Produits ménagers'], 'libelle' => 'Liquide vaisselle Sun 750 ml', 'description' => 'Détergent puissant pour une vaisselle impeccable.', 'code_article'=>'ART-20240526-16'],
            ['id_cat' => $categories['Produits ménagers'], 'libelle' => 'Lessive en poudre Ariel 3 kg', 'description' => 'Lessive efficace contre les taches tenaces.', 'code_article'=>'ART-20240526-17'],
            ['id_cat' => $categories['Produits ménagers'], 'libelle' => 'Désodorisant maison Febreze', 'description' => 'Désodorisant éliminant les mauvaises odeurs.', 'code_article'=>'ART-20240526-18'],

            ['id_cat' => $categories['Électronique'], 'libelle' => 'Terminal de validation de tickets', 'description' => 'Appareil utilisé pour vérifier la validité des tickets de loterie.', 'code_article'=>'ART-20240526-19'],
            ['id_cat' => $categories['Électronique'], 'libelle' => 'Machine à tirage électronique', 'description' => 'Système utilisé pour générer aléatoirement les numéros gagnants.', 'code_article'=>'ART-20240526-20'],
            ['id_cat' => $categories['Électronique'], 'libelle' => 'Écran LED affichage résultats', 'description' => 'Grand écran pour afficher les résultats en temps réel.', 'code_article'=>'ART-20240526-21'],

            ['id_cat' => $categories['Informatique'], 'libelle' => 'Ordinateur de gestion des paris', 'description' => 'PC puissant dédié à la gestion des transactions de loterie.', 'code_article'=>'ART-20240526-22'],
            ['id_cat' => $categories['Informatique'], 'libelle' => 'Imprimante thermique de tickets', 'description' => 'Imprimante spécialisée pour l’impression des tickets de jeu.', 'code_article'=>'ART-20240526-23'],
            ['id_cat' => $categories['Informatique'], 'libelle' => 'Serveur de stockage sécurisé', 'description' => 'Serveur haute performance pour stocker les données des joueurs.', 'code_article'=>'ART-20240526-24'],

            ['id_cat' => $categories['Fournitures de bureau'], 'libelle' => 'Carnets de reçus pour points de vente', 'description' => 'Reçus destinés aux clients après validation des jeux.', 'code_article'=>'ART-20240526-25'],
            ['id_cat' => $categories['Fournitures de bureau'], 'libelle' => 'Stylos bille LNB', 'description' => 'Stylos personnalisés pour les bureaux de la loterie.', 'code_article'=>'ART-20240526-26'],
            ['id_cat' => $categories['Fournitures de bureau'], 'libelle' => 'Cartouches d’encre pour imprimantes', 'description' => 'Encre de rechange pour les imprimantes des agences.', 'code_article'=>'ART-20240526-27'],

            ['id_cat' => $categories['Vêtements et accessoires'], 'libelle' => 'Uniforme agents de loterie', 'description' => 'Vêtements officiels pour les employés de la LNB.', 'code_article'=>'ART-20240526-36'],
            ['id_cat' => $categories['Vêtements et accessoires'], 'libelle' => 'Casquettes promotionnelles', 'description' => 'Casquettes floquées au logo de la LNB pour événements.', 'code_article'=>'ART-20240526-28'],
            ['id_cat' => $categories['Vêtements et accessoires'], 'libelle' => 'Montres personnalisées', 'description' => 'Montres-bracelets distribuées aux employés et partenaires.', 'code_article'=>'ART-20240526-29'],

            ['id_cat' => $categories['Alimentaire'], 'libelle' => 'Packs de bouteilles d’eau', 'description' => 'Eau minérale fournie aux employés et lors des événements.', 'code_article'=>'ART-20240526-30'],
            ['id_cat' => $categories['Alimentaire'], 'libelle' => 'Boissons énergétiques', 'description' => 'Boissons pour les longues heures de travail.', 'code_article'=>'ART-20240526-31'],
            ['id_cat' => $categories['Alimentaire'], 'libelle' => 'Snacks pour réunions', 'description' => 'Collations légères pour les pauses et réunions internes.', 'code_article'=>'ART-20240526-32'],

            ['id_cat' => $categories['Produits ménagers'], 'libelle' => 'Nettoyant multi-surfaces', 'description' => 'Produit d’entretien pour les bureaux et locaux de la LNB.', 'code_article'=>'ART-20240526-33'],
            ['id_cat' => $categories['Produits ménagers'], 'libelle' => 'Gel hydroalcoolique', 'description' => 'Désinfectant pour l’hygiène dans les points de vente.', 'code_article'=>'ART-20240526-34'],
            ['id_cat' => $categories['Produits ménagers'], 'libelle' => 'Désodorisant de bureau', 'description' => 'Désodorisant pour garder les bureaux agréablement parfumés.', 'code_article'=>'ART-20240526-35'],
        ];

        foreach ($articles as $article) {
            Article::firstOrCreate($article);
        }

        $this->command->info('Les articles ont été ajoutés avec succès !');
    }
}
