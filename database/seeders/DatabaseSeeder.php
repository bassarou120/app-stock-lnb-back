<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            RoleSeeder::class,
            TypeMouvementSeeder::class,
            CategorieSeeder::class,
            FournisseurSeeder::class,
            ArticlesSeeder::class,
            TypeAffectationSeeder::class,
            EmployerSeeder::class,
            BureauSeeder::class,
            CompagniePetrolierSeeder::class,
            MarqueSeeder::class,
            StatusImmoSeeder::class,
            TypeImmoSeeder::class,
            SousTypeImmoSeeder::class,
            GroupeTypeImmoSeeder::class,
            ModeleSeeder::class,
            VoitureSeeder::class,
            CommuneSeeder::class,
            CouponTicketSeeder::class,
            UserSeeder::class,
            ModuleSeeder::class,
            FonctionnaliteSeeder::class,
            PermissionSeeder::class,
            UniteDeMesureSeeder::class,
            TypeInterventionSeeder::class,
        ]);

        // Appel de la commande artisan personnalisée
        Artisan::call('app:sync-stock-tickets');

        // Message dans la console pour confirmation
        $this->command->info('✅ StockTickets synchronisés avec succès.');
    }
}
