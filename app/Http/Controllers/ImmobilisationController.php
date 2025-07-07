<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class ImmobilisationController extends Controller
{
    // Afficher la liste des immobilisations
    /**
 * @OA\Get(
 *     path="/api/immobilisations",
 *     tags={"Immobilisations"},
 *     summary="Liste des immobilisations",
 *     @OA\Response(
 *         response=200,
 *         description="Liste récupérée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Liste des immobilisations"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Immobilisation")
 *             )
 *         )
 *     )
 * )
 */
    public function index()
    {
        $immos = Immobilisation::with([
            'vehicule',
            'groupeTypeImmo',
            'sousTypeImmo',
            'statusImmo',
            'employe',
            'bureau',
            'fournisseur'
        ])->where('isdeleted', false)
        ->latest()->paginate(100);

        return new PostResource(true, 'Liste des immobilisations', $immos);
    }

    // Créer une nouvelle immobilisation

    /**
 * @OA\Post(
 *     path="/api/immobilisations",
 *     tags={"Immobilisations"},
 *     summary="Créer une nouvelle immobilisation",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_groupe_type_immo", "id_sous_type_immo", "id_status_immo"},
 *             @OA\Property(property="designation", type="string", example="Scanner HP"),
 *             @OA\Property(property="code", type="string", example="IMMO-2025-002"),
 *             @OA\Property(property="montant_ttc", type="integer", example=250000),
 *             @OA\Property(property="date_acquisition", type="string", format="date"),
 *             @OA\Property(property="date_mise_en_service", type="string", format="date"),
 *             @OA\Property(property="id_groupe_type_immo", type="integer", example=1),
 *             @OA\Property(property="id_sous_type_immo", type="integer", example=1),
 *             @OA\Property(property="id_status_immo", type="integer", example=1),
 *             @OA\Property(property="fournisseur_id", type="integer"),
 *             @OA\Property(property="employe_id", type="integer"),
 *             @OA\Property(property="bureau_id", type="integer"),
 *             @OA\Property(property="vehicule_id", type="integer"),
 *             @OA\Property(property="isVehicule", type="boolean", example=false)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Immobilisation créée",
 *         @OA\JsonContent(ref="#/components/schemas/PostResourceImmobilisationResponse")
 *     ),
 *     @OA\Response(response=422, description="Erreur de validation")
 * )
 */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_id' => 'nullable|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'date_mouvement' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'designation' => 'nullable|string|max:255',
            'isVehicule' => 'boolean',
            'vehicule_id' => 'nullable|exists:vehicules,id',
            'code' => 'nullable|string|max:255',
            'id_groupe_type_immo' => 'required|exists:groupe_type_immos,id',
            'id_sous_type_immo' => 'required|exists:sous_type_immos,id',
            'duree_amorti' => 'nullable|integer',
            'etat' => 'nullable|string',
            'taux_ammortissement' => 'nullable|integer',
            'duree_ammortissement' => 'nullable|integer',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'observation' => 'nullable|string',
            'id_status_immo' => 'required|exists:status_immos,id',
            'montant_ttc' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $immo = Immobilisation::create($request->all());

        return new PostResource(true, 'Immobilisation créée avec succès', $immo);
    }

    // Mettre à jour une immobilisation existante

    /**
 * @OA\Put(
 *     path="/api/immobilisations/{id}",
 *     tags={"Immobilisations"},
 *     summary="Mettre à jour une immobilisation",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'immobilisation",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_groupe_type_immo", "id_sous_type_immo", "id_status_immo"},
 *             @OA\Property(property="designation", type="string", example="Scanner HP"),
 *             @OA\Property(property="code", type="string", example="IMMO-2025-002"),
 *             @OA\Property(property="montant_ttc", type="integer", example=250000),
 *             @OA\Property(property="date_acquisition", type="string", format="date"),
 *             @OA\Property(property="date_mise_en_service", type="string", format="date"),
 *             @OA\Property(property="id_groupe_type_immo", type="integer", example=1),
 *             @OA\Property(property="id_sous_type_immo", type="integer", example=1),
 *             @OA\Property(property="id_status_immo", type="integer", example=1),
 *             @OA\Property(property="fournisseur_id", type="integer"),
 *             @OA\Property(property="employe_id", type="integer"),
 *             @OA\Property(property="bureau_id", type="integer"),
 *             @OA\Property(property="vehicule_id", type="integer"),
 *             @OA\Property(property="isVehicule", type="boolean", example=false)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Immobilisation mise à jour",
 *         @OA\JsonContent(ref="#/components/schemas/PostResourceImmobilisationResponse")
 *     )
 * )
 */
    public function update(Request $request, Immobilisation $immobilisation)
    {
        $validator = Validator::make($request->all(), [
            'bureau_id' => 'nullable|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'date_mouvement' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'designation' => 'nullable|string|max:255',
            'isVehicule' => 'boolean',
            'vehicule_id' => 'nullable|exists:vehicules,id',
            'code' => 'nullable|string|max:255',
            'id_groupe_type_immo' => 'required|exists:groupe_type_immos,id',
            'id_sous_type_immo' => 'required|exists:sous_type_immos,id',
            'duree_amorti' => 'nullable|integer',
            'etat' => 'nullable|string',
            'taux_ammortissement' => 'nullable|integer',
            'duree_ammortissement' => 'nullable|integer',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'observation' => 'nullable|string',
            'id_status_immo' => 'required|exists:status_immos,id',
            'montant_ttc' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $immobilisation->update($request->all());

        return new PostResource(true, 'Immobilisation mise à jour avec succès', $immobilisation);
    }

    // Supprimer une immobilisation

    /**
 * @OA\Delete(
 *     path="/api/immobilisations/{id}",
 *     tags={"Immobilisations"},
 *     summary="Supprimer une immobilisation",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Immobilisation supprimée",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Immobilisation supprimée avec succès"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     )
 * )
 */
    public function destroy(Immobilisation $immobilisation)
    {
        $immobilisation->isdeleted = true;
        $immobilisation->save();

        return new PostResource(true, 'Immobilisation supprimée avec succès', null);
    }

    public function imprimerImmos()
    {
        // Récupère toutes les immobilisations avec leurs relations nécessaires
        $immobilisations = Immobilisation::with([
            'vehicule',
            'groupeTypeImmo',
            'sousTypeImmo',
            'statusImmo',
            'employe',
            'bureau',
            'fournisseur'
        ])
        ->where('isdeleted', false)
        ->latest()->get();

        $pdf = \Pdf::loadView('pdf.immobilisations', compact('immobilisations'));

        return $pdf->download('liste_immobilisations.pdf');
    }
}
