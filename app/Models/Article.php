<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\CategorieArticle;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;
    protected $fillable = [
        'libelle',
        'description',
        'id_cat',
        'stock_alerte',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieArticle::class, 'id_cat');
    }

    // Relation avec le stock
    public function stock()
    {
        return $this->hasOne(Stock::class, 'id_Article', 'id');
    }
    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }
}
