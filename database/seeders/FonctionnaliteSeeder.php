<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Module;
use App\Models\Parametrage\Fonctionnalite;


class FonctionnaliteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Définition des modules et de leurs fonctionnalités
        $modules_fonctionnalites = [
            'Gestion de Stock' => [
                'Ajout du Stock',
                'Modification du Stock',
                'Suppression du Stock',
                'Voir Les demandes',
                'Validation de demande',
                'Sorties de Stock',
                'Voir Etat de Stock',
                'Export Stock',
                'Export Rapport Stock'
            ],
            'Gestion de immobilisation' => [
                'Ajout immobilisation',
                'Modification immobilisation',
                'Suppression immobilisation',
                'Affectation Immobilisation',
                'Intervention Immobilisation',
                'Export Rapport Immobilisation'
            ],
            'Gestion de parc' => [
                'Ajout parc',
                'Modification parc',
                'Suppression parc',
                'Intervention Parc',
                "Ajout d'intervention",
                'Attribution ticket',
                'Ajout de Ticket',
                'Verifier Stock Ticket',
                'Voir Retour Ticket',
                'Annulation Ticket',
                'Export Rapport Parc'

            ],
            'Parametrage' => [
                'Ajout Parametrage',
                'Modification Parametrage',
                'Suppression Parametrage',
            ],
            'Gestion Rapport' => [
                'Rapport Stock',
                'Rapport Immo',
                'Rapport Parc',
                'Rapport Ticket',
                'Export Rapport Immo'
            ],
        ];

        foreach ($modules_fonctionnalites as $module_libelle => $fonctionnalites) {
            // Récupérer l'ID du module
            $module = Module::where('libelle_module', $module_libelle)->first();

            if (!$module) {
                $this->command->warn("Le module '$module_libelle' n'existe pas. Vérifiez votre table modules.");
                continue;
            }

            // Ajouter les fonctionnalités en évitant les doublons
            foreach ($fonctionnalites as $fonction) {
                Fonctionnalite::firstOrCreate([
                    'libelle_fonctionnalite' => $fonction,
                    'module_id' => $module->id,
                ]);
            }

            $this->command->info("Les fonctionnalités du module '$module_libelle' ont été ajoutées !");
        }
    }
}
