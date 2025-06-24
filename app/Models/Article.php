<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\CategorieArticle;
use App\Models\MouvementStock; // Import nécessaire
use App\Models\Stock;          // Import nécessaire
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Parametrage\UniteDeMesure; // Si vous avez cette relation sur l'article


class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'description',
        'id_cat',
        'stock_alerte',
        'code_article',
        // Si 'id_unite_de_mesure' est une colonne dans la table 'articles' et doit être mass-assignable
        'id_unite_de_mesure', 
        // Si 'prix_unitaire' est une colonne dans la table 'articles' et doit être mass-assignable
        'prix_unitaire' 
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieArticle::class, 'id_cat');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'id_Article', 'id');
    }

    public function mouvementStocks(): HasMany
    {
        // Confirmez que 'id_Article' est bien la clé étrangère dans 'mouvement_stocks'
        return $this->hasMany(MouvementStock::class, 'id_Article');
    }

    // AJOUTÉ (si l'article a une unité de mesure directe)
    public function uniteDeMesure()
    {
        return $this->belongsTo(UniteDeMesure::class, 'id_unite_de_mesure');
    }
}
