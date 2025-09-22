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
        return $this->belongsToMany(
            Article::class,             // modèle lié
            'article_exercice',       // nom exact de la table pivot
            'id_exercice',              // clé étrangère vers Exercice
            'id_article'                // clé étrangère vers Article
        )
        ->withPivot('stock_debut_exercice', 'stock_fin_exercice', 'cmp_debut_exercice', 'cmp_fin_exercice')
        ->withTimestamps();
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

    public function mouvementTickets()
    {
        return $this->belongsToMany(MouvementTicket::class, 'exercice_mouvement_stock', 'exercice_id', 'coupon_ticket_id');
    }

    public function exerciceMouvementTickets()
    {
        return $this->hasMany(ExerciceMouvementTicket::class, 'exercice_id');
    }

}
