<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Commune;
use Illuminate\Support\Facades\DB;


class CommuneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$communes = [
            // Alibori
            ['libelle_commune' => 'Banikoara', 'departement' => 'Alibori'],
            ['libelle_commune' => 'Gogounou', 'departement' => 'Alibori'],
            ['libelle_commune' => 'Kandi', 'departement' => 'Alibori'],
            ['libelle_commune' => 'Karimama', 'departement' => 'Alibori'],
            ['libelle_commune' => 'Malanville', 'departement' => 'Alibori'],
            ['libelle_commune' => 'Ségbana', 'departement' => 'Alibori'],

            // Atacora
            ['libelle_commune' => 'Boukoumbé', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Cobly', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Kérou', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Kouandé', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Matéri', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Natitingou', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Pehunco', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Tanguiéta', 'departement' => 'Atacora'],
            ['libelle_commune' => 'Toucountouna', 'departement' => 'Atacora'],

            // Atlantique
            ['libelle_commune' => 'Abomey-Calavi', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Allada', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Kpomassè', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Ouidah', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Sô-Ava', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Toffo', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Tori-Bossito', 'departement' => 'Atlantique'],
            ['libelle_commune' => 'Zè', 'departement' => 'Atlantique'],

            // Borgou
            ['libelle_commune' => 'Bembèrèkè', 'departement' => 'Borgou'],
            ['libelle_commune' => 'Kalalé', 'departement' => 'Borgou'],
            ['libelle_commune' => 'N’Dali', 'departement' => 'Borgou'],
            ['libelle_commune' => 'Nikki', 'departement' => 'Borgou'],
            ['libelle_commune' => 'Parakou', 'departement' => 'Borgou'],
            ['libelle_commune' => 'Pèrèrè', 'departement' => 'Borgou'],
            ['libelle_commune' => 'Sinendé', 'departement' => 'Borgou'],
            ['libelle_commune' => 'Tchaourou', 'departement' => 'Borgou'],

            // Collines
            ['libelle_commune' => 'Bantè', 'departement' => 'Collines'],
            ['libelle_commune' => 'Dassa-Zoumè', 'departement' => 'Collines'],
            ['libelle_commune' => 'Glazoué', 'departement' => 'Collines'],
            ['libelle_commune' => 'Ouèssè', 'departement' => 'Collines'],
            ['libelle_commune' => 'Savalou', 'departement' => 'Collines'],
            ['libelle_commune' => 'Savè', 'departement' => 'Collines'],

            // Couffo
            ['libelle_commune' => 'Aplahoué', 'departement' => 'Couffo'],
            ['libelle_commune' => 'Djakotomey', 'departement' => 'Couffo'],
            ['libelle_commune' => 'Dogbo', 'departement' => 'Couffo'],
            ['libelle_commune' => 'Klouékanmè', 'departement' => 'Couffo'],
            ['libelle_commune' => 'Lalo', 'departement' => 'Couffo'],
            ['libelle_commune' => 'Toviklin', 'departement' => 'Couffo'],

            // Donga
            ['libelle_commune' => 'Bassila', 'departement' => 'Donga'],
            ['libelle_commune' => 'Copargo', 'departement' => 'Donga'],
            ['libelle_commune' => 'Djougou', 'departement' => 'Donga'],
            ['libelle_commune' => 'Ouaké', 'departement' => 'Donga'],

            // Littoral
            ['libelle_commune' => 'Cotonou', 'departement' => 'Littoral'],

            // Mono
            ['libelle_commune' => 'Athiémè', 'departement' => 'Mono'],
            ['libelle_commune' => 'Bopa', 'departement' => 'Mono'],
            ['libelle_commune' => 'Comè', 'departement' => 'Mono'],
            ['libelle_commune' => 'Grand-Popo', 'departement' => 'Mono'],
            ['libelle_commune' => 'Houéyogbé', 'departement' => 'Mono'],
            ['libelle_commune' => 'Lokossa', 'departement' => 'Mono'],

            // Ouémé
            ['libelle_commune' => 'Adjarra', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Adjohoun', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Aguégués', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Akpro-Missérété', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Avrankou', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Bonou', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Dangbo', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Porto-Novo', 'departement' => 'Ouémé'],
            ['libelle_commune' => 'Sèmè-Kpodji', 'departement' => 'Ouémé'],

            // Plateau
            ['libelle_commune' => 'Adja-Ouèrè', 'departement' => 'Plateau'],
            ['libelle_commune' => 'Ifangni', 'departement' => 'Plateau'],
            ['libelle_commune' => 'Kétou', 'departement' => 'Plateau'],
            ['libelle_commune' => 'Pobè', 'departement' => 'Plateau'],
            ['libelle_commune' => 'Sakété', 'departement' => 'Plateau'],

            // Zou
            ['libelle_commune' => 'Abomey', 'departement' => 'Zou'],
            ['libelle_commune' => 'Agbangnizoun', 'departement' => 'Zou'],
            ['libelle_commune' => 'Bohicon', 'departement' => 'Zou'],
            ['libelle_commune' => 'Covè', 'departement' => 'Zou'],
            ['libelle_commune' => 'Djidja', 'departement' => 'Zou'],
            ['libelle_commune' => 'Ouinhi', 'departement' => 'Zou'],
            ['libelle_commune' => 'Za-Kpota', 'departement' => 'Zou'],
            ['libelle_commune' => 'Zagnanado', 'departement' => 'Zou'],
            ['libelle_commune' => 'Zogbodomey', 'departement' => 'Zou'],
        ];

        // DB::table('communes')->insert($communes);

        foreach ($communes as $commune) {
            Commune::firstOrCreate($commune);
        }
    }
}
