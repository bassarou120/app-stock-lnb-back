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
                'Voir les entrées',
                'Ajout du Stock',
                'Modification du Stock',
                'Suppression du Stock',
                'Voir Les demandes',
                'Validation de demande',
                'Refus de demande',
                'Accorder de demande',
                'Sorties de Stock',
                'Voir Etat de Stock',
                'Export Stock',
                'Export Rapport Stock'
            ],
            'Gestion de immobilisation' => [
                'Voir les immobilisations',
                'Ajout immobilisation',
                'Modification immobilisation',
                'Suppression immobilisation',
                'Voir les Transferts',
                'Voir les Interventions Immo',
                'Export Rapport Immobilisation',
                'Exporter immobilisation',
                'Ajout intervention',
                'Modification intervention',
                'Suppression intervention',
            ],
            'Gestion de parc' => [
                'Voir parc vehicule',
                'Voir intervention vehicule',
                'Voir entrée de ticket',
                'Ajout vehicule',
                'Modification vehicule',
                'Suppression vehicule',
                'Ajout Intervention vehicule',
                'Modification Intervention vehicule',
                'Suppression Intervention vehicule',
                'Intervention Parc',
                'Attribution ticket',
                'Ajout de Ticket',
                'Modification de Ticket',
                'Supression de Ticket',
                'Verifier Stock Ticket',
                'Export Rapport Parc',
                'Voir Retour Ticket',
                'Ajout Retour Ticket',
                'Modification Retour Ticket',
                'Supprimer Retour Ticket',
                'Annulation Ticket',
                'Voir Annulation Ticket',
                'Ajout Annulation Ticket',

            ],
            'Parametrage' => [
                'Ajout Parametrage',
                'Voir permissions',
                'Suppression Parametrage',
                'Ajout role',
                'Voir Parametres Stock',
                'Voir Parametres Parc',
                'Voir Parametres Immo',
                'Voir Parametres Généraux'
            ],
            'Gestion Rapport' => [
                'Rapport Stock',
                'Rapport Immo',
                'Rapport Parc',
                'Rapport Ticket',
                'Export Rapport Immo'
            ],
            'Gestion des utilisateurs' => [
                'Ajout utilisateur',
                'Voir utilisateur',
                'Modification utilisateur',
                'Suppression utilisateur',
                'Exporter utilisateur',
                'Voir role'
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