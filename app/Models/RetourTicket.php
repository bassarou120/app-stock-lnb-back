<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\CouponTicket;
use App\Models\Parametrage\CompagniePetrolier;
use App\Models\Parametrage\Employe;

class RetourTicket extends Model
{
    use HasFactory;

    protected $fillable = ['mouvementTicket_id', 'coupon_ticket_id', 'compagnie_petrolier_id', 'qte'];


    // Relation avec le modèle Article
    public function mouvement()
    {
        return $this->belongsTo(MouvementTicket::class, 'mouvementTicket_id');
    }
    public function coupon()
    {
        return $this->belongsTo(CouponTicket::class, 'coupon_ticket_id');
    }
    public function campagnie()
    {
        return $this->belongsTo(CompagniePetrolier::class, 'compagnie_petrolier_id');
    }
    public function employe()
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }

}
