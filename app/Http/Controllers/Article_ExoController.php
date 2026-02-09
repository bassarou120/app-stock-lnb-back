<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleExercice;
use App\Models\Article;
use App\Models\Exercice;
use App\Http\Resources\PostResource;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class Article_ExoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Récupère toutes les entrées de la table pivot
        $articleExercices = ArticleExercice::all();

        // 📝 LOG → Consultation de la liste des articles
        LogJournalisation::create([
            'action'     => 'Consultation de la liste des articles par exercices',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Retourne la vue avec les données
        return new PostResource(true, 'Liste des articles', $articleExercices);
    }

    public function articlesExercices(Request $request)
    {
        // Eager load the 'article' and 'exercice' relationships.
        // This fetches the related data in a single query for each relationship.
        $articlesExercices = ArticleExercice::with(['article', 'exercice'])
        ->orderBy('id_exercice', 'desc')
        ->get();

        // 📝 LOG → Consultation de la liste complète des articles_exercices
        LogJournalisation::create([
            'action'     => 'Consultation de la liste complète des articles_exercices',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Return the data as a JSON response.
        return new PostResource(true, 'Liste complète des articles_exercices', $articlesExercices);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données entrantes
        $request->validate([
            'id_article' => 'required|exists:articles,id',
            'id_exercice' => 'required|exists:exercices,id',
            'stock_debut_exercice' => 'nullable|integer',
            'stock_fin_exercice' => 'nullable|integer',
        ]);

        // Crée une nouvelle instance de l'association
        $articleExercice = ArticleExercice::create($request->all());
        // 📝 LOG → Création d'une nouvelle association Article ↔ Exercice
        LogJournalisation::create([
            'action'     => 'Création de l\'association Article-Exercice ID Article: '.$request->id_article.' / ID Exercice: '.$request->id_exercice,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Redirection avec un message de succès
        return redirect()->route('article_exercice.index')->with('success', 'Association créée avec succès.');

        return new PostResource(true, 'Liste des articles', $articleExercices);
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article, Exercice $exercice, Request $request)
    {
        // Recherche l'entrée spécifique en utilisant les IDs des deux clés
        $articleExercice = ArticleExercice::where('id_article', $article->id)
                                          ->where('id_exercice', $exercice->id)
                                          ->firstOrFail();

        // 📝 LOG → Consultation d'une association Article ↔ Exercice spécifique
        LogJournalisation::create([
            'action'     => 'Consultation de l\'association Article ID: '.$article->id.' ↔ Exercice ID: '.$exercice->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Retourne la vue avec l'entrée spécifique
        return new PostResource(true, 'Liste des articles', $articleExercice);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article, Exercice $exercice)
    {
        // Validation des données entrantes
        $request->validate([
            'stock_debut_exercice' => 'nullable|integer',
            'stock_fin_exercice' => 'nullable|integer',
/*             'cmp_debut_exercice' => 'nullable|numeric|between:0,9999999.99',
            'cmp_fin_exercice' => 'nullable|numeric|between:0,9999999.99', */
        ]);

        // Recherche l'entrée spécifique en utilisant les IDs des deux clés
        $articleExercice = ArticleExercice::where('id_article', $article->id)
                                          ->where('id_exercice', $exercice->id)
                                          ->firstOrFail();

        // Met à jour l'entrée avec les nouvelles données
        $articleExercice->update($request->all());

        // 📝 LOG → Mise à jour d'une association Article ↔ Exercice
        LogJournalisation::create([
            'action'     => 'Mise à jour de l\'association Article ID: '.$article->id.' ↔ Exercice ID: '.$exercice->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Redirection avec un message de succès
        // return redirect()->route('article_exercice.show', [$article, $exercice])->with('success', 'Association mise à jour avec succès.');
        return new PostResource(true, 'Association mise à jour avec succès.', $articleExercice);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        //
    }
}
