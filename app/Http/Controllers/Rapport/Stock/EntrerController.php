<?php

namespace App\Http\Controllers\Rapport\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\Parametrage\TypeMouvement;
use App\Http\Resources\PostResource;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class EntrerController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function rapport_EntreeStock(Request $request)
    {

        // ✅ Vérifie que les deux dates sont fournies
        if (!$request->filled('date_debut') || !$request->filled('date_fin')) {
            return response()->json([
                'success' => false,
                'message' => 'Les champs "date_debut" et "date_fin" sont obligatoires.'
            ], 422);
        }

        // ✅ Requête de base avec relations
        $query = MouvementStock::with(['article', 'fournisseur', 'piecesJointes', 'article.categorie', 'article.stock', 'unite_de_mesure'])
            ->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

        // 🔍 Filtrer par type de mouvement (optionnel)
        if ($request->filled('id_type_mouvement')) {
            $query->where('id_type_mouvement', $request->id_type_mouvement);
        }

        // 🔍 Filtrer par article (optionnel)
        if ($request->filled('id_Article')) {
            $query->where('id_Article', $request->id_Article);
        }

        // 🔍 Filtrer par fournisseur (optionnel)
        if ($request->filled('id_fournisseur')) {
            $query->where('id_fournisseur', $request->id_fournisseur);
        }

        // ✅ Exécute la requête
        $resultats = $query->latest()->paginate(1000);
        LogJournalisation::create([
            "action"      => "Consulatat du rapport des entrées de stock",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Mouvements filtrés avec succès.', $resultats);
    }



    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


}
