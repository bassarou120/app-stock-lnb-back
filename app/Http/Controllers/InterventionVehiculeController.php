<?php

namespace App\Http\Controllers;

use App\Models\InterventionVehicule;
use App\Models\Parametrage\TypeIntervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PostResource;

class InterventionVehiculeController extends Controller
{
    // Afficher la liste des intervention

    /**
 * @OA\Get(
 *     path="/api/intervention-vehicules",
 *     tags={"Intervention Véhicule"},
 *     summary="Lister les interventions des véhicules",
 *     @OA\Response(
 *         response=200,
 *         description="Liste récupérée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Liste des interventions de véhicules"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/InterventionVehicule")
 *             )
 *         )
 *     )
 * )
 */

    public function index()
    {
        $interventions = InterventionVehicule::with([
            'vehicule',
            'typeIntervention'
        ])->latest()->paginate(100);

        return new PostResource(true, 'Liste des interventions de véhicules', $interventions);
    }


    // Créer une nouvelle intervention

    /**
 * @OA\Post(
 *     path="/api/intervention-vehicules",
 *     tags={"Intervention Véhicule"},
 *     summary="Créer une nouvelle intervention de véhicule",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"vehicule_id", "titre", "montant", "date_intervention", "type_intervention_id"},
 *             @OA\Property(property="vehicule_id", type="integer", example=1),
 *             @OA\Property(property="titre", type="string", example="Vidange"),
 *             @OA\Property(property="montant", type="number", format="float", example=25000),
 *             @OA\Property(property="observation", type="string", example="RAS"),
 *             @OA\Property(property="date_intervention", type="string", format="date", example="2025-06-15"),
 *             @OA\Property(property="type_intervention_id", type="integer", example=3),
 *             @OA\Property(property="date_expiration", type="string", format="date", example="2025-12-15")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Intervention créée",
 *         @OA\JsonContent(ref="#/components/schemas/PostResourceInterventionResponse")
 *     ),
 *     @OA\Response(response=422, description="Erreur de validation")
 * )
 */

   public function store(Request $request)
   {
        $validator = Validator::make($request->all(), [
            'vehicule_id' => 'required|exists:vehicules,id',
            'titre' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0', // Changé à 'numeric' pour accepter les décimales si nécessaire, ou 'integer' si entier seulement
            'observation' => 'nullable|string',
            'date_intervention' => 'required|date',
            'type_intervention_id' => 'required|exists:type_interventions,id',
            'date_expiration' => 'nullable|date', // AJOUTÉ: Rendre date_expiration nullable et de type date
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Crée l'intervention avec toutes les données validées
        $interventionVehicule = InterventionVehicule::create($request->all());

        return new PostResource(true, 'intervention créée avec succès', $interventionVehicule);
   }

    /**
     * Update the specified resource in storage.
     */

     /**
 * @OA\Put(
 *     path="/api/intervention-vehicules/{id}",
 *     tags={"Intervention Véhicule"},
 *     summary="Mettre à jour une intervention",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'intervention",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/InterventionVehicule")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Intervention mise à jour avec succès",
 *         @OA\JsonContent(ref="#/components/schemas/PostResourceInterventionResponse")
 *     )
 * )
 */

    public function update(Request $request, InterventionVehicule $interventionVehicule)
    {

        $validator = Validator::make($request->all(), [
            'vehicule_id' => 'required|exists:vehicules,id',
            'titre' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0', // Changé à 'numeric'
            'observation' => 'nullable|string',
            'date_intervention' => 'required|date',
            'type_intervention_id' => 'required|exists:type_interventions,id',
            'date_expiration' => 'nullable|date', // AJOUTÉ: Rendre date_expiration nullable et de type date
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Met à jour l'intervention avec toutes les données validées
        $interventionVehicule->update($request->all());

        return new PostResource(true, 'intervention mise à jour avec succès', $interventionVehicule);
    }

    public function Intervention_Vehicule()
    {
        $interventions = TypeIntervention::where("applicable_seul_vehicule", true)
        ->latest()
        ->paginate(100);

    return new PostResource(true, 'Liste des interventions vehicules', $interventions);
    }

    /**
 * @OA\Delete(
 *     path="/api/intervention-vehicules/{id}",
 *     tags={"Intervention Véhicule"},
 *     summary="Supprimer une intervention",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Intervention supprimée",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="intervention supprimé avec succès")
 *         )
 *     )
 * )
 */

    public function destroy($id)
    {
        $intervention = InterventionVehicule::findOrFail($id);
        $intervention->delete();
        return response()->json(['message' => 'intervention supprimé avec succès']);
    }

    public function imprimerInterventionsVehicule()
    {
        $interventions = InterventionVehicule::with([
            'vehicule',
            'typeIntervention'
        ])->latest()->get();

        $pdf = \Pdf::loadView('pdf.interventions_vehicule', compact('interventions'));

        return $pdf->download('liste_interventions_vehicule.pdf');
    }
}
