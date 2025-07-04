<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Fonctionnalite;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class FonctionnaliteController extends Controller
{
    // Afficher une liste paginée des fonctionnalités
    public function index()
    {
        $fonctionnalites = Fonctionnalite::with('module')->where('isdeleted', false)->latest()->paginate(200);
        return new PostResource(true, 'Liste des fonctionnalités', $fonctionnalites);
    }

    // Créer une nouvelle fonctionnalité
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'libelle_fonctionnalite' => 'required|unique:fonctionnalites,libelle_fonctionnalite',
            'module_id' => 'required|exists:modules,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer une fonctionnalité
        $fonctionnalite = Fonctionnalite::create([
            'libelle_fonctionnalite' => $request->libelle_fonctionnalite,
            'module_id' => $request->module_id,
        ]);

        return new PostResource(true, 'Fonctionnalité créée avec succès', $fonctionnalite);
    }

    // Mettre à jour une fonctionnalité
    public function update(Request $request, Fonctionnalite $fonctionnalite)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'libelle_fonctionnalite' => 'required|unique:fonctionnalites,libelle_fonctionnalite,' . $fonctionnalite->id,
            'module_id' => 'required|exists:modules,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mise à jour de la fonctionnalité
        $fonctionnalite->update([
            'libelle_fonctionnalite' => $request->libelle_fonctionnalite,
            'module_id' => $request->module_id,
        ]);

        return new PostResource(true, 'Fonctionnalité mise à jour avec succès', $fonctionnalite);
    }

    // Supprimer une fonctionnalité
    public function destroy(Fonctionnalite $fonctionnalite)
    {
        $fonctionnalite->isdeleted = true;
        $fonctionnalite->save();
        return new PostResource(true, 'Fonctionnalité supprimée avec succès', null);
    }
}
