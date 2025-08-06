<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\Fournisseur;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use App\Models\Parametrage\StatusImmo;
use App\Models\Vehicule;
use PhpOffice\PhpSpreadsheet\IOFactory;



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
            'reference_estampillonnage' => 'nullable|string|max:255',
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
            'reference_estampillonnage' => 'nullable|string|max:255',
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

    public function import(Request $request)
    {
        // 1️⃣ Validation
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2️⃣ Charger le fichier
        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // 3️⃣ Boucler sur les lignes (en ignorant la première ligne d'entêtes)
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Ignore header

            // 🛡️ Vérifie que la ligne a bien au moins 21 colonnes
            if (count($row) < 20) {
                // Tu peux logguer ou ignorer cette ligne
                \Log::warning("Ligne $index ignorée : colonnes insuffisantes (" . count($row) . ")");
                echo "Ligne $index ignorée : colonnes insuffisantes (" . count($row) . ")";
                continue;
            }

            $bureau = $row[0];
            $employe_id = $row[1];
            $date_mouvement = $row[2];
            $fournisseur = $row[3];
            $designation = $row[4];
            $isVehicule = $row[5];
            $vehicule = $row[6];
            $code = $row[7];
            $groupe_type_immo = $row[8];
            $sous_type_immo = $row[9];
            $duree_amorti = $row[10];
            $etat = $row[11];
            $taux_ammortissement = $row[12];
            $duree_ammortissement = $row[13];
            $date_acquisition = $row[14];
            $date_mise_en_service = $row[15];
            $observation = $row[16];
            $status_immo = $row[17];
            $montant_ttc = $row[18];
            $reference_estampillonnage = $row[19];


            // 🔎 Trouver les IDs correspondants
            $bureau_id = Bureau::firstOrCreate(['libelle_bureau' => $bureau]);
            $fournisseur_id = Fournisseur::firstOrCreate(['nom' => $fournisseur]);
            $vehicule_id = null;
            $id_groupe_type_immo = GroupeTypeImmo::firstOrCreate(['libelle' => $groupe_type_immo, 'compte'=> 000000]);
            $id_sous_type_immo = SousTypeImmo::firstOrCreate(['libelle' => $sous_type_immo]);
            $id_status_immo = StatusImmo::firstOrCreate(['libelle_status_immo' => $status_immo]);


            //  Créer l'immo
            Immobilisation::create([
                'bureau_id' => $bureau_id->id,
                'employe_id' => $employe_id,
                'date_mouvement' => \Carbon\Carbon::createFromFormat('m/d/Y', $date_mouvement)->format('Y-m-d'),
                'fournisseur_id' => $fournisseur_id->id,
                'designation' => $designation,
                'isVehicule' => false,
                'vehicule_id' => null,
                'code' => $code,
                'id_groupe_type_immo' => $id_groupe_type_immo->id,
                'id_sous_type_immo' => $id_sous_type_immo->id,
                'duree_amorti' => $duree_amorti,
                'etat' => $etat,
                'taux_ammortissement' => $taux_ammortissement,
                'duree_ammortissement' => $duree_ammortissement,
                'date_acquisition' => \Carbon\Carbon::createFromFormat('m/d/Y', $date_acquisition)->format('Y-m-d'),
                'date_mise_en_service' =>  \Carbon\Carbon::createFromFormat('m/d/Y', $date_mise_en_service)->format('Y-m-d'),
                'observation' => $observation,
                'id_status_immo' => $id_status_immo->id,
                'montant_ttc' => $montant_ttc,
                'reference_estampillonnage' => $reference_estampillonnage,
            ]);
        }

        return response()->json(['message' => 'Import réussi !']);
    }





}
