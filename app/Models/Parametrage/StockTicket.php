<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class StockTicket extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'coupon_ticket_id',
        'qte_actuel',
        'compagnie_petrolier_id'
    ];

    public function couponTicket()
    {
        return $this->belongsTo(CouponTicket::class, 'coupon_ticket_id');
    }
    public function compagnie()
    {
        return $this->belongsTo(CompagniePetrolier::class, 'compagnie_petrolier_id');
    }
}
