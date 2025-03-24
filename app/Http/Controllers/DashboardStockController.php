<?php

namespace App\Http\Controllers;
use App\Http\Resources\PostResource;
use App\Models\Article;
use Illuminate\Http\Request;

class DashboardStockController extends Controller
{
    public function indexArticles()
    {
        $articles = Article::with(['categorie', 'stock'])->latest()->paginate(1000);
        return new PostResource(true, 'Liste des articles', $articles);
    }
    public function articlesEnAlerte()
    {
        $articles = Article::with(['categorie', 'stock'])->latest()->paginate(1000);
        return new PostResource(true, 'Liste des articles', $articles);
    }

    public function dashInfoStock()
{
    // Construction de la requête
    $query = Article::with('stock')
        ->where(function ($q) {
            $q->whereHas('stock', function ($subQuery) {
                $subQuery->whereColumn('Qte_actuel', '<=', 'articles.stock_alerte');
            })
            ->orWhereDoesntHave('stock');
        });

    // Exécution
    $articles = $query->latest()->get();
    $total_article_en_alerte = $articles->count();

    $total_article = Article::latest()->get()->count();

    return new PostResource(true, 'Articles en alerte', [
        'total_article_en_alerte' => $total_article_en_alerte,
        'total_article' => $total_article,
    ]);
}

}
