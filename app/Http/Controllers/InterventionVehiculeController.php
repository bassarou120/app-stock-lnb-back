<?php

namespace App\Http\Controllers;

use App\Models\InterventionVehicule;
use App\Models\Parametrage\TypeIntervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PostResource;
use Carbon\Carbon;

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
        ])
        ->where('isdeleted', false)
        ->latest()->paginate(100);

        return new PostResource(true, 'Liste des interventions de véhicules', $interventions);
    }

    public function Intervention_vehicule()
    {
        $interventions = TypeIntervention::where("applicable_seul_vehicule", true)
        ->latest()
        ->where('isdeleted', false)
        ->paginate(100);

        return new PostResource(true, 'Liste des interventions immos', $interventions);
    }

public function getVehiculesAssuranceExpireSoon()
{
    $today = Carbon::now();

    // On eager load la relation "vehicule.marque"
    $interventions = InterventionVehicule::with(['vehicule.marque'])
        ->where('isdeleted', false)
        ->get()
        ->filter(function ($intervention) use ($today) {
            $expiration = Carbon::parse($intervention->date_expiration);
            $diffInMonths = $today->diffInMonths($expiration, false);
            return $diffInMonths >= 0 && $diffInMonths <= 3;
        })
        ->map(function ($intervention) use ($today) {
            $expiration = Carbon::parse($intervention->date_expiration);
            $vehicule = $intervention->vehicule;
            return [
                'id' => $intervention->id,
                'vehicule_id' => $intervention->vehicule_id,
                'titre' => $intervention->titre,
                'montant' => $intervention->montant,
                'observation' => $intervention->observation,
                'type_intervention_id' => $intervention->type_intervention_id,
                'date_intervention' => Carbon::parse($intervention->date_intervention)->format('Y-m-d'),
                'date_expiration' => $expiration->format('Y-m-d'),
                'created_at' => Carbon::parse($intervention->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($intervention->updated_at)->format('Y-m-d H:i:s'),
                'jours_restants' => (int) $today->diffInDays($expiration, false),

                // Infos du véhicule
                'vehicule' => [
                    'immatriculation' => $vehicule->immatriculation,
                    'marque' => $vehicule->marque->libelle ?? null,
                ],
            ];
        })
        ->sortBy('date_expiration')
        ->values();

    return response()->json([
        'success' => true,
        'message' => 'Véhicules avec assurance expirant dans 3 mois ou moins',
        'data' => $interventions,
    ]);
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

    public function InterventionVehicule()
    {
        $interventions = TypeIntervention::where("applicable_seul_vehicule", true)
        ->latest()
        ->where('isdeleted', false)
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

        $intervention->isdeleted = true;
        $intervention->save();
        return response()->json(['message' => 'intervention supprimé avec succès']);
    }

    public function imprimerInterventionsVehicule()
    {
        $interventions = InterventionVehicule::with([
            'vehicule',
            'typeIntervention'
        ])->where('isdeleted', false)
        ->latest()->get();

        $pdf = \Pdf::loadView('pdf.interventions_vehicule', compact('interventions'));

        return $pdf->download('liste_interventions_vehicule.pdf');
    }
}

