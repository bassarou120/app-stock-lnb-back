<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;
use App\Models\Parametrage\UniteDeMesure;
use Illuminate\Support\Facades\Validator;

class UniteDeMesureController extends Controller
{
    // Afficher la liste des UniteDeMesures
    public function index()
    {
        $uniteDeMesures = UniteDeMesure::latest()->paginate(100);
        return new PostResource(true, 'Liste des Unites De Mesure', $uniteDeMesures);
    }

    // Créer un nouveau UniteDeMesures
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $uniteDeMesure = UniteDeMesure::create([
            'libelle' => $request->libelle,
        ]);

        return new PostResource(true, 'unite De Mesure créé avec succès', $uniteDeMesure);
    }

    // Mettre à jour un uniteDeMesure existant
    public function update(Request $request, UniteDeMesure $unite_de_mesure)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $unite_de_mesure->update([
            'libelle' => $request->libelle,
        ]);

        return new PostResource(true, 'UniteDeMesure mis à jour avec succès', $unite_de_mesure);
    }

    // Supprimer un unite_de_mesure
    public function destroy(UniteDeMesure $unite_de_mesure)
    {
        $unite_de_mesure->delete();
        return new PostResource(true, 'unite_de_mesure supprimé avec succès', null);
    }
}
