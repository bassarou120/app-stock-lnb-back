<?php

namespace App\Http\Controllers;
use App\Models\ExerciceMouvementTicket;
use App\Models\Article;
use App\Models\Exercice;
use App\Http\Resources\PostResource;
use App\Models\MouvementTicket;
use App\Models\Parametrage\CouponTicket; // Added missing import
use App\Models\Parametrage\CompagniePetrolier; // Added missing import

use Illuminate\Http\Request;

class ExerciceMouvementTicketController extends Controller
{
    public function index()
    {
        // Retrieve all entries from the pivot table, eager-loading the related models.
        $associations = ExerciceMouvementTicket::with(['exercice', 'couponTicket', 'compagniePetrolier'])->get();

        // Return the data as a JSON response.
        return response()->json([
            'success' => true,
            'message' => 'Liste des associations exercice-mouvement de ticket récupérée avec succès.',
            'data' => $associations,
        ]);
    }

    public function store(Request $request)
    {
        // Validation des données entrantes
        $request->validate([
            'id_exercice' => 'required|exists:exercices,id',
            'id_mouvement' => 'required|exists:mouvement_tickets,id',
        ]);

        // Crée une nouvelle instance de l'association
        $exerciceMouvementTicket = ExerciceMouvementTicket::create($request->all());

        return new PostResource(true, 'Association créée avec succès.', $exerciceMouvementTicket);
    }

    public function show($exerciceId, $mouvementId)
    {
        // Recherche l'entrée spécifique en utilisant les IDs des deux clés
        $exerciceMouvementTicket = ExerciceMouvementTicket::where('id_exercice', $exerciceId)
                                                      ->where('id_mouvement', $mouvementId)
                                                      ->firstOrFail();

        return new PostResource(true, 'Association trouvée.', $exerciceMouvementTicket);
    }

    public function update(Request $request, $id)
    {
        // La mise à jour d'une table de jointure est peu courante, car elle ne contient généralement que des clés.
        // On suppose ici qu'il pourrait y avoir des champs supplémentaires à mettre à jour.
        // Si tu n'as pas d'autres champs, tu peux ignorer cette méthode ou la simplifier.

        $exerciceMouvementTicket = ExerciceMouvementTicket::findOrFail($id);
        $exerciceMouvementTicket->update($request->all());

        return new PostResource(true, 'Association mise à jour avec succès.', $exerciceMouvementTicket);
    }

    public function destroy($id)
    {
        $exerciceMouvementTicket = ExerciceMouvementTicket::findOrFail($id);
        $exerciceMouvementTicket->delete();

        return new PostResource(true, 'Association supprimée avec succès.');
    }
}
