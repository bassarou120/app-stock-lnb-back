<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\CategorieArticle;
use App\Models\MouvementStock; // Import nécessaire
use App\Models\Stock;          // Import nécessaire
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Parametrage\UniteDeMesure; // Si vous avez cette relation sur l'article
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="Article",
 *     title="Article",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="id_cat", type="integer", example=3),
 *     @OA\Property(property="libelle", type="string", example="Chaussures de sport"),
 *     @OA\Property(property="code_article", type="string", example="ART-2025-001"),
 *     @OA\Property(property="description", type="string", example="Chaussures confortables pour la course"),
 *     @OA\Property(property="stock_alerte", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(
 *         property="categorie",
 *         ref="#/components/schemas/CategorieArticle"
 *     ),
 *     @OA\Property(
 *         property="stock",
 *         ref="#/components/schemas/Stock"
 *     )
 * )
 */

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
        'prix_unitaire',
        'id_exercice',
        'demande_intermittent'
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

    public function exercices(): BelongsToMany
    {
        return $this->belongsToMany(
            Exercice::class,            // modèle lié
            'article_exercice',       // nom exact de la table pivot
            'id_article',               // clé étrangère vers Article
            'id_exercice'               // clé étrangère vers Exercice
        )
        ->withPivot('stock_debut_exercice', 'stock_fin_exercice', 'cmp_debut_exercice', 'cmp_fin_exercice')
        ->withTimestamps();

    }

    // Relation hasMany (tous les stocks de l'article, tous exercices confondus)
public function stocks()
{
    return $this->hasMany(Stock::class, 'id_Article', 'id');
}

// Retourne le stock pour un exercice précis
public function stockPourExercice($idExercice)
{
    return $this->stocks()->where('id_exercice', $idExercice)->first();
}

}
