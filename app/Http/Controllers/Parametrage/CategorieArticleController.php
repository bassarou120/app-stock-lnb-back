<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\CategorieArticle;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class CategorieArticleController extends Controller
{
    // Afficher la liste des catégories d'articles
    public function index(Request $request)
    {
        $categories = CategorieArticle::latest()->where('isdeleted', false)->paginate(1000);
        LogJournalisation::create([
            "action"      => "Consultation de la liste des catégories d'articles",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des catégories d\'articles', $categories);
    }

    // Créer une nouvelle catégorie d'article
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_categorie_article' => 'required|string|max:255',
            // 'valeur' => 'nullable|string|max:255',
            // 'taux' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $categorie = CategorieArticle::create([
            'libelle_categorie_article' => $request->libelle_categorie_article,
            // 'valeur' => $request->valeur,
            // 'taux' => $request->taux,
        ]);

        LogJournalisation::create([
            "action"      => "Création d'une nouvelle catégorie d'article",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Catégorie d\'article créée avec succès', $categorie);
    }

    // Mettre à jour une catégorie d'article existante
    public function update(Request $request, CategorieArticle $categorie_article)
    {
        $validator = Validator::make($request->all(), [
            'libelle_categorie_article' => 'required|string|max:255',
            // 'valeur' => 'nullable|string|max:255',
            // 'taux' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $categorie_article->update([
            'libelle_categorie_article' => $request->libelle_categorie_article,
            // 'valeur' => $request->valeur,
            // 'taux' => $request->taux,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour d'une catégorie d'article",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Catégorie d\'article mise à jour avec succès', $categorie_article);
    }

    // Supprimer une catégorie d'article
    public function destroy(CategorieArticle $categorie_article, Request $request)
    {
        $categorie_article->isdeleted = true;
        $categorie_article->save();
        LogJournalisation::create([
            "action"      => "Suppression de la catégorie d'article : " . $categorie_article->libelle_categorie_article,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'Catégorie d\'article supprimée avec succès', null);
    }
}
