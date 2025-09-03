<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExerciceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startYear = 2020;
        $currentYear = Carbon::now()->year;

        for ($year = $startYear; $year <= $currentYear; $year++) {
            // Vérifier si l'année existe déjà
            $exists = DB::table('exercices')->where('annee', $year)->exists();
            if ($exists) {
                continue; // Passer à l'année suivante
            }

            // Définir le statut : ouvert si c'est l'année en cours, sinon clôturé
            $statut = ($year === $currentYear) ? 'ouvert' : 'cloture';

            // Début et fin d'année
            $dateDebut = Carbon::create($year, 1, 1)->toDateString();
            $dateFin = Carbon::create($year, 12, 31)->toDateString();

            DB::table('exercices')->insert([
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'annee' => $year,
                'statut' => $statut,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
