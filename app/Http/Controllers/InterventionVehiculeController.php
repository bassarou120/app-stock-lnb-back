<?php

namespace App\Http\Controllers;

use App\Models\InterventionVehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PostResource;

class InterventionVehiculeController extends Controller
{
    // Afficher la liste des intervention
    public function index()
    {
        $interventions = InterventionVehicule::with([
            'vehicules',
        ])->latest()->paginate(100);
 
        return new PostResource(true, 'Liste des interventions de véhicules', $interventions);
    }

    
    // Créer une nouvelle intervention
   public function store(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'vehicule_id' => 'required|exists:vehicules,id',
           'titre' => 'required|string|max:255',
           'montant' => 'required|integer|min:0',
           'observation' => 'nullable|string',
           'date_intervention' => 'required|date',
       ]);

       if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

       $interventionVehicule = InterventionVehicule::create($request->all());

       return new PostResource(true, 'intervention créée avec succès', $interventionVehicule);
   }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InterventionVehicule $interventionVehicule)
    {
        $validator = Validator::make($request->all(), [
            'vehicule_id' => 'required|exists:vehicules,id',
            'titre' => 'required|string|max:255',
            'montant' => 'required|integer|min:0',
            'observation' => 'nullable|string',
            'date_intervention' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $interventionVehicule->update($request->all());

        return new PostResource(true, 'intervention mise à jour avec succès', $interventionVehicule);
    }

    
    public function destroy(InterventionVehicule $interventionVehicule): JsonResponse
    {
        $interventionVehicule->delete();

        return new PostResource(true, 'intervention supprimée avec succès', null);
    }
}
