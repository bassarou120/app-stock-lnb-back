<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer l'ID du rôle 'Admin'
        $role = Role::where('libelle_role', 'Admin')->first();

        if (!$role) {
            throw new \Exception("Admin' n'existe pas.");
        }

        // Générer un mot de passe fort
        $password = 'MotDePasse123';
        $hashedPassword = Hash::make($password);

        // Vérifier si l'utilisateur existe déjà pour éviter les doublons
        $user = User::firstOrCreate(
            ['email' => 'admin@gmail.com'], // Email unique
            [
                'name' => 'Admin',
                'phone' => '0123456789',
                'active' => true,
                'sexe' => 'Masculin', 
                'role_id' => $role->id,
                'password' => $hashedPassword,
            ]
        );


        echo "Utilisateur 'Admin' créé.\n";
    }


}
