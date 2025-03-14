<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeAffectation;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class TypeAffectationController extends Controller
{
    // Afficher la liste des types d'affectation
    public function index()
    {
        $typesAffectation = TypeAffectation::latest()->paginate(100);
        return new PostResource(true, 'Liste des types d\'affectation', $typesAffectation);
    }

    // Créer un nouveau type d'affectation
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_affectation' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $typeAffectation = TypeAffectation::create([
            'libelle_type_affectation' => $request->libelle_type_affectation,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Type d\'affectation créé avec succès', $typeAffectation);
    }

    // Mettre à jour un type d'affectation existant
    public function update(Request $request, TypeAffectation $typeAffectation)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_affectation' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $typeAffectation->update([
            'libelle_type_affectation' => $request->libelle_type_affectation,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Type d\'affectation mis à jour avec succès', $typeAffectation);
    }

    // Supprimer un type d'affectation
    public function destroy(TypeAffectation $typeAffectation)
    {
        $typeAffectation->delete();
        return new PostResource(true, 'Type d\'affectation supprimé avec succès', null);
    }
}
