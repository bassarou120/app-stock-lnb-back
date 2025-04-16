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

    public function stock()
    {
        return $this->hasOne(StockTicket::class, 'coupon_ticket_id');
    }
}
