<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeMouvement;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class TypeMouvementController extends Controller
{
    // Afficher la liste des types de mouvement
    public function index()
    {
        $typesMouvement = TypeMouvement::latest()->where('isdeleted', false)->paginate(100);
        return new PostResource(true, 'Liste des types de mouvement', $typesMouvement);
    }

    // Créer un nouveau type de mouvement
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_mouvement' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $typeMouvement = TypeMouvement::create([
            'libelle_type_mouvement' => $request->libelle_type_mouvement,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Type de mouvement créé avec succès', $typeMouvement);
    }

    // Mettre à jour un type de mouvement existant
    public function update(Request $request, TypeMouvement $typeMouvement)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_mouvement' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $typeMouvement->update([
            'libelle_type_mouvement' => $request->libelle_type_mouvement,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Type de mouvement mis à jour avec succès', $typeMouvement);
    }

    // Supprimer un type de mouvement
    public function destroy(TypeMouvement $typeMouvement)
    {
        $typeMouvement->isdeleted = true;
        $typeMouvement->save();
        return new PostResource(true, 'Type de mouvement supprimé avec succès', null);
    }
}
