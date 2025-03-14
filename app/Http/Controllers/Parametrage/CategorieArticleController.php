<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\CategorieArticle;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class CategorieArticleController extends Controller
{
    // Afficher la liste des catégories d'articles
    public function index()
    {
        $categories = CategorieArticle::latest()->paginate(100);
        return new PostResource(true, 'Liste des catégories d\'articles', $categories);
    }

    // Créer une nouvelle catégorie d'article
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_categorie_article' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
            'taux' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $categorie = CategorieArticle::create([
            'libelle_categorie_article' => $request->libelle_categorie_article,
            'valeur' => $request->valeur,
            'taux' => $request->taux,
        ]);

        return new PostResource(true, 'Catégorie d\'article créée avec succès', $categorie);
    }

    // Mettre à jour une catégorie d'article existante
    public function update(Request $request, CategorieArticle $categorie)
    {
        $validator = Validator::make($request->all(), [
            'libelle_categorie_article' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
            'taux' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $categorie->update([
            'libelle_categorie_article' => $request->libelle_categorie_article,
            'valeur' => $request->valeur,
            'taux' => $request->taux,
        ]);

        return new PostResource(true, 'Catégorie d\'article mise à jour avec succès', $categorie);
    }

    // Supprimer une catégorie d'article
    public function destroy(CategorieArticle $categorie)
    {
        $categorie->delete();
        return new PostResource(true, 'Catégorie d\'article supprimée avec succès', null);
    }
}
