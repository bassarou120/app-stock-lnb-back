<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="CategorieArticle",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=3),
 *     @OA\Property(property="libelle_categorie_article", type="string", example="Chaussures")
 * )
 */


class CategorieArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_categorie_article',
        'valeur',
        'taux',
    ];
}