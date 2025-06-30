<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\CompagniePetrolier;
use App\Models\Parametrage\CouponTicket;
use App\Models\Parametrage\Commune;
use App\Models\Vehicule;


use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="MouvementTicket",
 *     required={"vehicule_id", "compagnie_petrolier_id", "coupon_ticket_id", "qte", "date"},
 *     @OA\Property(property="vehicule_id", type="integer"),
 *     @OA\Property(property="compagnie_petrolier_id", type="integer"),
 *     @OA\Property(property="coupon_ticket_id", type="integer"),
 *     @OA\Property(property="employe_id", type="integer"),
 *     @OA\Property(property="qte", type="integer"),
 *     @OA\Property(property="date", type="string", format="date"),
 *     @OA\Property(property="objet", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="kilometrage", type="integer"),
 *     @OA\Property(property="reference", type="string"),
 *     @OA\Property(property="commune_depart", type="integer"),
 *     @OA\Property(property="commune_arriver", type="integer"),
 *     @OA\Property(property="trajet_aller_retour", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */


class MouvementTicket extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $fillable = [];

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
    public function depart()
    {
        return $this->belongsTo(Commune::class, 'commune_depart');
    }

    public function arriver()
    {
        return $this->belongsTo(Commune::class, 'commune_arriver');
    }
}
