<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

/**
 * @OA\Schema(
 *     schema="Stock",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="id_Article", type="integer", example=1),
 *     @OA\Property(property="Qte_actuel", type="integer", example=10)
 * )
 */

class Stock extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['id_Article', 'Qte_actuel', 'id_exercice', 'cout_moyen_pondere'];


    // Relation avec le modèle Article
    public function article()
    {
        return $this->belongsTo(Article::class, 'id_Article');
    }
}
