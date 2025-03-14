<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Modele;
use App\Http\Resources\PostResource; 
use Illuminate\Support\Facades\Validator;

class ModeleController extends Controller
{
    // Afficher la liste des modèles
    public function index()
    {
        $modeles = Modele::latest()->paginate(100);

        return new PostResource(true, 'Liste des modèles', $modeles);
    }

    // Créer un nouveau modèle
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_modele' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $modele = Modele::create([
            'libelle_modele' => $request->libelle_modele,
        ]);

        return new PostResource(true, 'Modèle créé avec succès', $modele);
    }

    // Mettre à jour un modèle existant
    public function update(Request $request, Modele $modele)
    {
        $validator = Validator::make($request->all(), [
            'libelle_modele' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $modele->update([
            'libelle_modele' => $request->libelle_modele,
        ]);

        return new PostResource(true, 'Modèle mis à jour avec succès', $modele);
    }

    // Supprimer un modèle
    public function destroy(Modele $modele)
    {
        $modele->delete();

        return new PostResource(true, 'Modèle supprimé avec succès', null);
    }
}
