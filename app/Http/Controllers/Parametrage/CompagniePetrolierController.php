<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\CompagniePetrolier;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class CompagniePetrolierController extends Controller
{
    // Afficher la liste des compagnies pétrolières
    public function index()
    {
        // Récupérer toutes les compagnies pétrolières triées par ordre décroissant
        $compagnies = CompagniePetrolier::latest()->paginate(100);

        // Retourner la réponse formatée avec PostResource
        return new PostResource(true, 'Liste des compagnies pétrolières', $compagnies);
    }

    // Créer une nouvelle compagnie pétrolière
    public function store(Request $request)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer une nouvelle compagnie pétrolière avec les données valides
        $compagnie = CompagniePetrolier::create([
            'libelle' => $request->libelle,
            'adresse' => $request->adresse,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la création a réussi
        return new PostResource(true, 'Compagnie pétrolière créée avec succès !', $compagnie);
    }

    // Mettre à jour une compagnie pétrolière existante
    public function update(Request $request, CompagniePetrolier $compagnie_petrolier)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mettre à jour la compagnie pétrolière avec les nouvelles données
        $compagnie_petrolier->update([
            'libelle' => $request->libelle,
            'adresse' => $request->adresse,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'Compagnie pétrolière modifiée avec succès', $compagnie_petrolier);
    }

    // Supprimer une compagnie pétrolière
    public function destroy(CompagniePetrolier $compagnie_petrolier)
    {
        // Supprimer la compagnie pétrolière
        $compagnie_petrolier->delete();

        // Retourner la réponse formatée avec PostResource, indiquant que la suppression a réussi
        return new PostResource(true, 'Compagnie pétrolière supprimée avec succès', null);
    }
}
