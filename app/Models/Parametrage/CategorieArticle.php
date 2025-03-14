<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Factories\HasFactory;


class CategorieArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_categorie_article',
        'valeur',
        'taux',
    ];
}
