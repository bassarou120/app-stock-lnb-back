<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Module;
use App\Models\Parametrage\Role;
use App\Models\Parametrage\Permission;
use App\Models\Parametrage\Fonctionnalite;


class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Définition des permissions pour chaque rôle avec leurs modules et fonctionnalités
        $permissions = [
            'Super Admin' => [
                'Gestion de Stock' => [
                    'Ajout du Stock',
                    'Modification du Stock',
                    'Suppression du Stock',
                ],
                'Gestion de immobilisation' => [
                    'Ajout immobilisation',
                    'Modification immobilisation',
                    'Suppression immobilisation',
                ],
                'Gestion de parc' => [
                    'Ajout parc',
                    'Modification parc',
                    'Suppression parc',
                ],
                'Gestion Rapport' => [
                'Rapport Stock',
                'Rapport Immo',
                'Rapport Parc',
                'Rapport Ticket',
                ],
                'Parametrage' => [
                    'Ajout Parametrage',
                    'Modification Parametrage',
                    'Suppression Parametrage',
                ],
            ],
            'Admin' => [
                'Gestion de Stock' => [
                    'Ajout du Stock',
                    'Modification du Stock',
                    'Suppression du Stock',
                ],
                'Gestion de immobilisation' => [
                    'Ajout immobilisation',
                    'Modification immobilisation',
                    'Suppression immobilisation',
                ],
                'Gestion de parc' => [
                    'Ajout parc',
                    'Modification parc',
                    'Suppression parc',
                ],
                'Gestion Rapport' => [
                'Rapport Stock',
                'Rapport Immo',
                'Rapport Parc',
                'Rapport Ticket',
            ],
                'Parametrage' => [
                    'Ajout Parametrage',
                    'Modification Parametrage',
                    'Suppression Parametrage',
                ],
            ],
            'Manager' => [
                'Gestion de Stock' => [
                    'Ajout du Stock',
                    'Modification du Stock',
                    'Suppression du Stock',
                ],
                'Gestion de immobilisation' => [
                    'Ajout immobilisation',
                    'Modification immobilisation',
                    'Suppression immobilisation',
                ],
                'Gestion de parc' => [
                    'Ajout parc',
                    'Modification parc',
                    'Suppression parc',
                ],
                'Gestion Rapport' => [
                'Rapport Stock',
                'Rapport Immo',
                'Rapport Parc',
                'Rapport Ticket',
            ],
                'Parametrage' => [
                    'Ajout Parametrage',
                    'Modification Parametrage',
                    'Suppression Parametrage',
                ],
            ],
        ];

        // Parcourir les rôles et les permissions à ajouter
        foreach ($permissions as $role_libelle => $modules) {
            // Récupérer l'ID du rôle
            $role = Role::where('libelle_role', $role_libelle)->first();

            if (!$role) {
                $this->command->warn("Le rôle '$role_libelle' n'existe pas. Vérifiez votre table roles.");
                continue;
            }

            // Parcourir les modules et leurs fonctionnalités
            foreach ($modules as $module_libelle => $fonctionnalites) {
                // Récupérer l'ID du module
                $module = Module::where('libelle_module', $module_libelle)->first();

                if (!$module) {
                    $this->command->warn("Le module '$module_libelle' n'existe pas. Vérifiez votre table modules.");
                    continue;
                }

                // Ajouter les permissions pour chaque fonctionnalité du module
                foreach ($fonctionnalites as $fonction_libelle) {
                    // Récupérer l'ID de la fonctionnalité
                    $fonctionnalite = Fonctionnalite::where('libelle_fonctionnalite', $fonction_libelle)
                        ->where('module_id', $module->id)
                        ->first();

                    if (!$fonctionnalite) {
                        $this->command->warn("La fonctionnalité '$fonction_libelle' n'existe pas dans le module '$module_libelle'. Vérifiez votre table fonctionnalités.");
                        continue;
                    }

                    // Créer la permission si elle n'existe pas déjà
                    Permission::firstOrCreate([
                        'role_id' => $role->id,
                        'module_id' => $module->id,
                        'fonctionnalite_id' => $fonctionnalite->id,
                        'is_active' => true, // définir à true ou selon ta logique
                    ]);
                }
            }
        }

        $this->command->info('Les permissions ont été ajoutées avec succès !');
    }
}
