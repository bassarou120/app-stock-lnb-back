<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Parametrage\StockTicket;


class CouponTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'valeur',
    ];

    // public function stock()
    // {
    //     return $this->hasOne(StockTicket::class, 'coupon_ticket_id');
    // }

    public function couponTicket()
    {
        return $this->belongsTo(CouponTicket::class, 'coupon_ticket_id');
    }
    public function compagnie()
    {
        return $this->belongsTo(CompagniePetrolier::class, 'compagnie_petrolier_id');
    }

    protected static function booted()
{
    static::created(function ($coupon) {
        // Pour chaque compagnie existante, on crée un stock_ticket
        $compagnies = \App\Models\Parametrage\CompagniePetrolier::all();
        foreach ($compagnies as $compagnie) {
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
