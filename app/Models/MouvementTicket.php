<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\CompagniePetrolier;
use App\Models\Parametrage\CouponTicket;
use App\Models\Vehicule;


use Illuminate\Database\Eloquent\Model;

class MouvementTicket extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
    public function compagniePetrolier()
    {
        return $this->belongsTo(CompagniePetrolier::class, 'compagnie_petrolier_id');
    }
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }
    public function coupon_ticket()
    {
        return $this->belongsTo(CouponTicket::class, 'coupon_ticket_id');
    }
}
