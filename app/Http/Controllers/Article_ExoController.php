<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleExercice;
use App\Models\Article;
use App\Models\Exercice;

class Article_ExoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Récupère toutes les entrées de la table pivot
        $articleExercices = ArticleExercice::all();

        // Retourne la vue avec les données
        return new PostResource(true, 'Liste des articles', $articleExercices);
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

        // Redirection avec un message de succès
        return redirect()->route('article_exercice.index')->with('success', 'Association créée avec succès.');

        return new PostResource(true, 'Liste des articles', $articleExercices);
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article, Exercice $exercice)
    {
        // Recherche l'entrée spécifique en utilisant les IDs des deux clés
        $articleExercice = ArticleExercice::where('id_article', $article->id)
                                          ->where('id_exercice', $exercice->id)
                                          ->firstOrFail();

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

        // Redirection avec un message de succès
        // return redirect()->route('article_exercice.show', [$article, $exercice])->with('success', 'Association mise à jour avec succès.');
        return new PostResource(true, 'Association mise à jour avec succès.', $articleExercice);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
