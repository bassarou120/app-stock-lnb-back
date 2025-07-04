<?php

namespace App\Http\Controllers;
use App\Http\Resources\PostResource;
use App\Models\Article;
use App\Models\MouvementStock; // Import the MouvementStock model
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
    // Articles en alerte (rupture ou pas)
    $query = Article::with('stock')
        ->where(function ($q) {
            $q->whereHas('stock', function ($subQuery) {
                $subQuery->whereColumn('Qte_actuel', '<=', 'articles.stock_alerte');
            })
            ->orWhereDoesntHave('stock');
        });

    $articles = $query->latest()->get();
    $total_article_en_alerte = $articles->count();

    // Articles dont le stock est en alerte MAIS qui ne sont pas en rupture totale (Qte_actuel > 0)
    $articles_stock_alerte_sans_rupture = Article::whereHas('stock', function ($q) {
        $q->whereColumn('Qte_actuel', '<=', 'articles.stock_alerte')
          ->where('Qte_actuel', '>', 0);
    })->count();

    // Total des articles
    $total_article = Article::count();

    // Demandes en attente
    $total_demandes_en_attente = MouvementStock::where('statut', 'En attente')->count();

    // Demandes accordées
    $total_demandes_accorde = MouvementStock::where('statut', 'Accordé')->count();

    return new PostResource(true, 'Données du dashboard stock', [
        'total_article_en_alerte' => $total_article_en_alerte,
        'article_stock_alerte_sans_rupture' => $articles_stock_alerte_sans_rupture,
        'total_article' => $total_article,
        'total_demandes_en_attente' => $total_demandes_en_attente,
        'total_demandes_accorde' => $total_demandes_accorde,
    ]);
}


}
