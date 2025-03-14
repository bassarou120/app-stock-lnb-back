<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Parametrage\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste des rôles à insérer
        $roles = ['Admin', 'Manager'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'libelle_role' => $role
            ]);
        }

        $this->command->info('Les rôles ont été ajoutés avec succès !');
    }
}
