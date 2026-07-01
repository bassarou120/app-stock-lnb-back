<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Parametrage\CompagniePetrolier;
use App\Models\Parametrage\CouponTicket;
use App\Traits\BelongsToTenant;


class ExerciceMouvementTicket extends Model
{
    //
    use HasFactory, BelongsToTenant;

    protected $table = 'exercice_mouvement_ticket';

    protected $fillable = [
        'exercice_id',
        'coupon_ticket_id',
        'compagnie_petrolier_id',
        'qte_actuel',
    ];

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class, 'exercice_id');
    }

    /**
     * Get the coupon ticket associated with the entry.
     */
    public function couponTicket(): BelongsTo
    {
        return $this->belongsTo(CouponTicket::class, 'coupon_ticket_id');
    }

    /**
     * Get the compagnie petrolier associated with the entry.
     */
    public function compagniePetrolier(): BelongsTo
    {
        return $this->belongsTo(CompagniePetrolier::class, 'compagnie_petrolier_id');
    }
}
