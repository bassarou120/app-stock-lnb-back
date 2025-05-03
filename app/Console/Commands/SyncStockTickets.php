<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Parametrage\CouponTicket;
use App\Models\Parametrage\CompagniePetrolier;
use App\Models\Parametrage\StockTicket;

class SyncStockTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-stock-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔄 Début de la synchronisation des stock_tickets...");

        // Récupération des données nécessaires
        $coupons = CouponTicket::all();
        $compagnies = CompagniePetrolier::all();
        $count = 0;

        // Parcourir toutes les combinaisons et créer les lignes manquantes
        foreach ($coupons as $coupon) {
            foreach ($compagnies as $compagnie) {
                $exists = StockTicket::where('coupon_ticket_id', $coupon->id)
                                     ->where('compagnie_petrolier_id', $compagnie->id)
                                     ->exists();

                if (! $exists) {
                    StockTicket::create([
                        'coupon_ticket_id' => $coupon->id,
                        'compagnie_petrolier_id' => $compagnie->id,
                        'qte_actuel' => 0,
                    ]);
                    $count++;
                }
            }
        }

        $this->info("✅ Synchronisation terminée. $count ligne(s) ajoutée(s).");
    }
}
