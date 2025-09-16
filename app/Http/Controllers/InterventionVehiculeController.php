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
        $sevenDaysAgo = $today->copy()->subDays(30);

        // CONVERTIR AU FORMAT Y-m-d AVANT LA REQUÊTE
        $sevenDaysAgoFormatted = $sevenDaysAgo->format('Y-m-d');
        $todayFormatted = $today->format('Y-m-d');

        // 1. ASSURANCES EXPIRANT DANS 3 MOIS OU MOINS
        $assurancesExpirantes = InterventionVehicule::with(['vehicule.marque'])
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
                    'vehicule' => [
                        'immatriculation' => $vehicule->immatriculation,
                        'marque' => $vehicule->marque->libelle ?? null,
                    ],
                ];
            })
            ->sortBy('date_expiration')
            ->values();

        // 2. RÉPARATIONS EFFECTUÉES DANS LES 7 DERNIERS JOURS
        $reparationsRecentes = InterventionVehicule::with(['vehicule.marque'])
            ->where('isdeleted', false)
            ->whereBetween('date_intervention', [$sevenDaysAgoFormatted, $todayFormatted]) // UTILISER LES VARIABLES FORMATÉES
            ->get()
            ->map(function ($intervention) {
                $vehicule = $intervention->vehicule;
                return [
                    'id' => $intervention->id,
                    'vehicule_id' => $intervention->vehicule_id,
                    'titre' => $intervention->titre,
                    'montant' => $intervention->montant,
                    'observation' => $intervention->observation,
                    'date_intervention' => Carbon::parse($intervention->date_intervention)->format('Y-m-d'),
                    'vehicule' => [
                        'immatriculation' => $vehicule->immatriculation,
                        'marque' => $vehicule->marque->libelle ?? null,
                    ],
                ];
            });

            // 3. VISITES TECHNIQUES PROCHES DE L'EXPIRATION (dans moins de 2 mois)
            $visitesTechniquesProches = InterventionVehicule::with(['vehicule.marque', 'typeIntervention'])
                ->where('isdeleted', false)
                ->whereHas('typeIntervention', function ($query) {
                    $query->where(function ($q) {
                        $q->where('libelle_type_intervention', 'ILIKE', '%visite technique%')
                        ->orWhere('libelle_type_intervention', 'ILIKE', '%contrôle technique%')
                        ->orWhere('libelle_type_intervention', 'ILIKE', '%inspection%');
                    })
                    ->where('isdeleted', false); // s'assurer que le type n'est pas supprimé
                })
                ->whereNotNull('date_expiration')
                ->get()
                ->filter(function ($intervention) use ($today) {
                    $expiration = Carbon::parse($intervention->date_expiration);
                    return $expiration->isAfter($today) && $today->diffInDays($expiration, false) < 20;
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
                        'jours_restants' => (int)$today->diffInDays($expiration, false),
                        'type_intervention' => $intervention->typeIntervention->libelle_type_intervention ?? null,
                        'vehicule' => [
                            'immatriculation' => $vehicule->immatriculation ?? null,
                            'marque' => $vehicule->marque->libelle ?? null,
                        ],
                    ];
                })
                ->sortBy('date_expiration')
                ->values();

            // 4. STATISTIQUES GLOBALES
            $nombreVehiculesAssuranceExpirante = $assurancesExpirantes->pluck('vehicule_id')->unique()->count();
            $nombreReparationsRecentes = $reparationsRecentes->count();
            $nombreVehiculesReparesRecemment = $reparationsRecentes->pluck('vehicule_id')->unique()->count();
            $nombreVehiculesVisiteTechniqueProche = $visitesTechniquesProches->pluck('vehicule_id')->unique()->count();


    return response()->json([
        'success' => true,
        'message' => 'Données dashboard véhicules',

        // DONNÉES ASSURANCES EXPIRANTES
        'assurances_expirantes' => $assurancesExpirantes,
        'nombre_vehicules_assurance_expirante' => $nombreVehiculesAssuranceExpirante,

        // DONNÉES RÉPARATIONS RÉCENTES
        'reparations_recentes' => $reparationsRecentes,
        'nombre_reparations_recentes' => $nombreReparationsRecentes,
        'nombre_vehicules_repares_recemment' => $nombreVehiculesReparesRecemment,

        // NOUVELLES DONNÉES VISITES TECHNIQUES
        'visites_techniques_proches' => $visitesTechniquesProches,
        'nombre_vehicules_visite_technique_proche' => $nombreVehiculesVisiteTechniqueProche,
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
