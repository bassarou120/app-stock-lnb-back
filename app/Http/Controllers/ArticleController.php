<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\CategorieArticle;
use App\Models\Article;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use Illuminate\Support\Facades\Log;



class ArticleController extends Controller
{
    // Afficher la liste des articles
    public function index()
    {
        $articles = Article::with(['categorie', 'stock'])->latest()->paginate(1000);
        return new PostResource(true, 'Liste des articles', $articles);
    }

    // Créer un nouveau article


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'id_cat' => 'required|exists:categorie_articles,id',
    //         'libelle' => 'required|string|max:255',
    //         'description' => 'required|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $article = Article::create([
    //         'id_cat' => $request->id_cat,
    //         'libelle' => $request->libelle,
    //         'description' => $request->description,
    //     ]);

    //     return new PostResource(true, 'Article créé avec succès', $article);
    // }

    // Nouvelle méthode pour ajouter plusieurs articles
    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'articles' => 'required|array',
            'articles.*.id_cat' => 'required|exists:categorie_articles,id',
            'articles.*.libelle' => 'required|string|max:255',
            'articles.*.description' => 'string|max:255',
            'articles.*.stock_alerte' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $articles = [];

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            foreach ($request->articles as $articleData) {
                $article = Article::create([
                    'id_cat' => $articleData['id_cat'],
                    'libelle' => $articleData['libelle'],
                    'description' => $articleData['description'],
                    'stock_alerte' => $articleData['stock_alerte'],
                ]);

                // Initialiser l'entrée de stock pour cet article
                Stock::create([
                    'id_Article' => $article->id,
                    'Qte_actuel' => 0
                ]);

                $articles[] = $article;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors de l\'enregistrement des articles.'], 500);
        }

        return new PostResource(true, count($articles) . ' articles créés et stocks initialisés avec succès', $articles);
    }

    // Mettre à jour un article existant
    public function update(Request $request, Article $article)
    {
        $validator = Validator::make($request->all(), [
            'id_cat' => 'required|exists:categorie_articles,id',
            'libelle' => 'required|string|max:255',
            'description' => 'string|max:255',
            'stock_alerte' => 'required|integer|min:0',
        ]);

        Log::info($request->all());


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $article->update([
            'id_cat' => $request->id_cat,
            'libelle' => $request->libelle,
            'description' => $request->description,
            'stock_alerte' => $request->stock_alerte,
        ]);

        return new PostResource(true, 'Article mis à jour avec succès', $article);
    }

    // Supprimer un article
    public function destroy(Article $article)
    {
        $article->delete();
        return new PostResource(true, 'Article supprimé avec succès', null);
    }

}
