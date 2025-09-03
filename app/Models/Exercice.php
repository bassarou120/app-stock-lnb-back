<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exercice extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_debut', 'date_fin', 'annee', 'statut'
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public function mouvementsStock()
    {
        return $this->hasMany(MouvementStock::class, 'id_exercice');
    }

    public function immobilisations()
    {
        return $this->hasMany(Immobilisation::class, 'id_exercice');
    }

    public function couponTickets()
    {
        return $this->hasMany(CouponTicket::class, 'id_exercice');
    }
}
