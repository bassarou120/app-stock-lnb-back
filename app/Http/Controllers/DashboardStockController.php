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
        // Construction de la requête pour les articles en alerte
        $query = Article::with('stock')
            ->where(function ($q) {
                $q->whereHas('stock', function ($subQuery) {
                    $subQuery->whereColumn('Qte_actuel', '<=', 'articles.stock_alerte');
                })
                ->orWhereDoesntHave('stock');
            });

        // Exécution pour les articles en alerte
        $articles = $query->latest()->get();
        $total_article_en_alerte = $articles->count();

        $total_article = Article::latest()->get()->count();

        // Récupérer le nombre de demandes en attente
        $total_demandes_en_attente = MouvementStock::where('statut', 'En attente')->count();

        // Récupérer le nombre de demandes Accordé
        $total_demandes_accorde = MouvementStock::where('statut', 'Accordé')->count();

        return new PostResource(true, 'Articles en alerte', [
            'total_article_en_alerte' => $total_article_en_alerte,
            'total_article' => $total_article,
            'total_demandes_en_attente' => $total_demandes_en_attente,
            'total_demandes_accorde' => $total_demandes_accorde,
        ]);
    }

}