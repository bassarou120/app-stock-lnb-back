<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompagniePetrolier extends Model
{
    use HasFactory;

    protected $table = 'compagnie_petroliers';
    protected $fillable = [
        'libelle',
        'adresse',
    ];

    protected static function booted()
{
    static::created(function ($compagnie) {
        // Pour chaque coupon existant, on crée un stock_ticket
        $coupons = \App\Models\Parametrage\CouponTicket::all();
        foreach ($coupons as $coupon) {
            \App\Models\Parametrage\StockTicket::firstOrCreate([
                'coupon_ticket_id' => $coupon->id,
                'compagnie_petrolier_id' => $compagnie->id,
            ], [
                'qte_actuel' => 0,
            ]);
        }
    });
}

}
