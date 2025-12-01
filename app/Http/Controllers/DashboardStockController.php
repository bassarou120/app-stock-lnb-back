<?php

namespace App\Http\Controllers;
use App\Http\Resources\PostResource;
use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\LogJournalisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardStockController extends Controller
{
    public function indexArticles(Request $request)
    {
        // 🔥 Log consultation articles
        LogJournalisation::create([
            'action'     => 'Consultation liste des articles',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        $articles = Article::with(['categorie', 'stock'])
            ->where('isdeleted', false)
            ->latest()
            ->paginate(1000);

        return new PostResource(true, 'Liste des articles', $articles);
    }

    public function articlesEnAlerte(Request $request)
    {
        // 🔥 Log consultation articles en alerte
        LogJournalisation::create([
            'action'     => 'Consultation des articles en alerte',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        $articles = Article::with(['categorie', 'stock'])
            ->where('isdeleted', false)
            ->latest()
            ->paginate(1000);

        return new PostResource(true, 'Liste des articles', $articles);
    }

    public function dashInfoStock(Request $request)
    {
        // 🔥 Log consultation dashboard stock
        LogJournalisation::create([
            'action'     => 'Consultation dashboard stock',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        // Articles en alerte
        $query = Article::with('stock')
            ->where('isdeleted', false)
            ->where(function ($q) {
                $q->whereHas('stock', function ($subQuery) {
                    $subQuery->whereColumn('Qte_actuel', '<=', 'articles.stock_alerte');
                })
                ->orWhereDoesntHave('stock');
            });

        $articles = $query->latest()->get();
        $total_article_en_alerte = $articles->count();

        // Articles en alerte mais non en rupture
        $articles_stock_alerte_sans_rupture = Article::whereHas('stock', function ($q) {
            $q->whereColumn('Qte_actuel', '<=', 'articles.stock_alerte')
              ->where('Qte_actuel', '>', 0);
        })->count();

        // Totaux
        $total_article = Article::count();
        $total_demandes_en_attente = MouvementStock::where('statut', 'En attente')->count();
        $total_demandes_accorde = MouvementStock::where('statut', 'Accordé')->count();

        return new PostResource(true, 'Données du dashboard stock', [
            'total_article_en_alerte'              => $total_article_en_alerte,
            'article_stock_alerte_sans_rupture'    => $articles_stock_alerte_sans_rupture,
            'total_article'                        => $total_article,
            'total_demandes_en_attente'            => $total_demandes_en_attente,
            'total_demandes_accorde'               => $total_demandes_accorde,
        ]);
    }
}
