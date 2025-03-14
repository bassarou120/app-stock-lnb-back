<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeIntervention;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class TypeInterventionController extends Controller
{
    // Afficher la liste des types d'intervention
    public function index()
    {
        $types = TypeIntervention::latest()->paginate(100);
        return new PostResource(true, 'Liste des types d\'intervention', $types);
    }

    // Créer un nouveau type d'intervention
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_intervention' => 'required|string|max:255',
            'applicable_seul_vehicule' => 'required|boolean',
            'observation' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type = TypeIntervention::create([
            'libelle_type_intervention' => $request->libelle_type_intervention,
            'applicable_seul_vehicule' => $request->applicable_seul_vehicule,
            'observation' => $request->observation,
        ]);

        return new PostResource(true, 'Type d\'intervention créé avec succès', $type);
    }

    // Mettre à jour un type d'intervention existant
    public function update(Request $request, TypeIntervention $type_intervention)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_intervention' => 'required|string|max:255',
            'applicable_seul_vehicule' => 'required|boolean',
            'observation' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_intervention->update([
            'libelle_type_intervention' => $request->libelle_type_intervention,
            'applicable_seul_vehicule' => $request->applicable_seul_vehicule,
            'observation' => $request->observation,
        ]);

        return new PostResource(true, 'Type d\'intervention mis à jour avec succès', $type_intervention);
    }

    // Supprimer un type d'intervention
    public function destroy(TypeIntervention $type_intervention)
    {
        $type_intervention->delete();
        return new PostResource(true, 'Type d\'intervention supprimé avec succès', null);
    }
}
