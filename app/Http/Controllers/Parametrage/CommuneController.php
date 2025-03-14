<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Commune;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class CommuneController extends Controller
{
    // Afficher la liste des communes
    public function index()
    {
        // Récupérer toutes les communes triées par ordre décroissant
        $communes = Commune::latest()->paginate(200);

        // Retourner la réponse formatée avec PostResource
        return new PostResource(true, 'Liste des communes', $communes);
    }

    // Créer une nouvelle commune
    public function store(Request $request)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle_commune' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer une nouvelle commune avec les données valides
        $commune = Commune::create([
            'libelle_commune' => $request->libelle_commune,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la création a réussi
        return new PostResource(true, 'Commune créée avec succès !', $commune);
    }

    // Mettre à jour une commune existante
    public function update(Request $request, Commune $commune)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle_commune' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mettre à jour la commune avec les nouvelles données
        $commune->update([
            'libelle_commune' => $request->libelle_commune,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'Commune modifiée avec succès', $commune);
    }

    // Supprimer une commune
    public function destroy(Commune $commune)
    {
        // Supprimer la commune
        $commune->delete();

        // Retourner la réponse formatée avec PostResource, indiquant que la suppression a réussi
        return new PostResource(true, 'Commune supprimée avec succès', null);
    }
}
