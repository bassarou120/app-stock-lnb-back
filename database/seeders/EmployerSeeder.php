<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\Employe;


class EmployerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employes = [
            [
                'nom' => 'Doe',
                'prenom' => 'John',
                'telephone' => '0612345678',
                'email' => 'john.doe@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Smith',
                'prenom' => 'Jane',
                'telephone' => '0698765432',
                'email' => 'jane.smith@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Martin',
                'prenom' => 'Paul',
                'telephone' => '0601020304',
                'email' => 'paul.martin@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Durand',
                'prenom' => 'Claire',
                'telephone' => '0611121314',
                'email' => 'claire.durand@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Nguyen',
                'prenom' => 'Linh',
                'telephone' => '0654321876',
                'email' => 'linh.nguyen@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Bernard',
                'prenom' => 'Luc',
                'telephone' => '0678945612',
                'email' => 'luc.bernard@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Lemoine',
                'prenom' => 'Sarah',
                'telephone' => '0645678901',
                'email' => 'sarah.lemoine@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Petit',
                'prenom' => 'Marc',
                'telephone' => '0623456789',
                'email' => 'marc.petit@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Dupont',
                'prenom' => 'Émilie',
                'telephone' => '0634567890',
                'email' => 'emilie.dupont@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Moreau',
                'prenom' => 'Hugo',
                'telephone' => '0687654321',
                'email' => 'hugo.moreau@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Roux',
                'prenom' => 'Laura',
                'telephone' => '0609090909',
                'email' => 'laura.roux@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Fournier',
                'prenom' => 'Antoine',
                'telephone' => '0666666666',
                'email' => 'antoine.fournier@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Chevalier',
                'prenom' => 'Nina',
                'telephone' => '0622223333',
                'email' => 'nina.chevalier@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Fabre',
                'prenom' => 'Thomas',
                'telephone' => '0677778888',
                'email' => 'thomas.fabre@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Lopez',
                'prenom' => 'Maria',
                'telephone' => '0612121212',
                'email' => 'maria.lopez@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($employes as $employe) {
            Employe::firstOrCreate($employe);
        }
    }
}
