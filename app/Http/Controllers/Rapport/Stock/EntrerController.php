<?php

namespace App\Http\Controllers\Rapport\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EntrerController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function rapport_EntreeStock(Request $request)
    {
        // ✅ Vérifier que les deux dates sont bien fournies
        if (!$request->filled('date_debut') || !$request->filled('date_fin')) {
            return response()->json([
                'success' => false,
                'message' => 'Les champs "date_debut" et "date_fin" sont obligatoires.'
            ], 422);
        }

        $type_mouvement = requets('id_type_mouvement');

        // ✅ Récupérer le type de mouvement "Entrée de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', $type_mouvement)->first();

        if (!$type_mouvement) {
            return new PostResource(false, "Type de mouvement" . $type_mouvement ." introuvable.", []);
        }

        // ✅ Construire la requête de base
        $query = MouvementStock::with(['article', 'fournisseur', 'piecesJointes'])
            ->where('id_type_mouvement', $type_mouvement->id)
            ->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

        // 🔍 Si libellé article fourni
        if ($request->filled('libelle')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('libelle', 'like', '%' . $request->libelle . '%');
            });
        }

        // 🔍 Si fournisseur fourni
        if ($request->filled('id_fournisseur')) {
            $query->where('id_fournisseur', $request->id_fournisseur);
        }

        // ✅ Exécuter la requête
        $resultats = $query->latest()->paginate(1000);

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
