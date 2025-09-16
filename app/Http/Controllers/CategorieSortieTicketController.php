<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategorieSortieTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class CategorieSortieTicketController extends Controller
{
     // Afficher la liste des communes
    public function index()
    {
        $categorieSortieTicket = CategorieSortieTicket::latest()->where('isdeleted', false)->paginate(1000);

        // Retourner la réponse formatée avec PostResource
        return new PostResource(true, 'Liste des CategorieSortieTicket', $categorieSortieTicket);
    }

    // Créer une nouvelle commune
    public function store(Request $request)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer une nouvelle commune avec les données valides
        $categorieSortieTicket = CategorieSortieTicket::create([
            'libelle' => $request->libelle,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la création a réussi
        return new PostResource(true, 'Commune créée avec succès !', $categorieSortieTicket);
    }

    // Mettre à jour une commune existante
    public function update(Request $request, CategorieSortieTicket $categorieSortieTicket)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mettre à jour la commune avec les nouvelles données
        $categorieSortieTicket->update([
            'libelle' => $request->libelle,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'categorieSortieTicket modifiée avec succès', $categorieSortieTicket);
    }

    // Supprimer une categorieSortieTicket
    public function destroy(CategorieSortieTicket $categorieSortieTicket)
    {
        // Supprimer la categorieSortieTicket
        $categorieSortieTicket->isdeleted = true;
        $categorieSortieTicket->save();
        // Retourner la réponse formatée avec PostResource, indiquant que la suppression a réussi
        return new PostResource(true, 'categorieSortieTicket supprimée avec succès', null);
    }

}