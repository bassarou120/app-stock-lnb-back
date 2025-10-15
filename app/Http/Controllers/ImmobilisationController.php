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
use App\Models\Parametrage\TypeImmo;
use App\Models\Parametrage\StatusImmo;
use App\Models\Vehicule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Transfert;

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
        ->latest()
        ->paginate(100);

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

        // Démarre une transaction de base de données
        DB::beginTransaction();

        try {
            // Création de l'immobilisation
            $immo = Immobilisation::create($request->all());
            //$immo = Immobilisation::create($immoData);

            // Crée un enregistrement de transfert si le bureau ou l'employé est renseigné
            if ($request->filled('bureau_id') || $request->filled('employe_id')) {
                Transfert::create([
                    'immo_id' => $immo->id, // On lie l'immobilisation au transfert
                    'old_bureau_id' => null, // Ancien bureau est "Magasin", donc null
                    'old_employe_id' => null, // Ancien employé est null
                    'bureau_id' => $request->get('bureau_id'),
                    'employe_id' => $request->get('employe_id'),
                    'date_mouvement' => now(), // Date du jour
                    'observation' => $request->observation,
                ]);
            }

            // Met à jour la date de mise en service de l'immobilisation
            $immo->date_mise_en_service = $immo->date_acquisition;
            $immo->save();

            // Si tout s'est bien passé, on valide la transaction
            DB::commit();

            return new PostResource(true, 'Immobilisation créée avec succès', $immo);

        } catch (\Exception $e) {
            // En cas d'erreur, on annule la transaction
            DB::rollBack();

            // Log l'erreur pour le débogage et retourne un message d'erreur
            \Log::error('Erreur lors de la création de l\'immobilisation et de son transfert : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'immobilisation.',
                'error' => $e->getMessage()
            ], 500);
        }
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
        ->latest()
        ->get();

        $pdf = \Pdf::loadView('pdf.immobilisations', compact('immobilisations'));

        return $pdf->download('liste_immobilisations.pdf');
    }

    public function import(Request $request)
    {
        // 1️⃣ Validation du fichier
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2️⃣ Chargement du fichier
        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $ignoredRows = [];
        $importedCount = 0;
        $realLine = 1; // correspond à la ligne Excel réelle (pour les logs)

        // 3️⃣ Boucle sur les lignes (en ignorant la première ligne d'entête)
        foreach ($rows as $index => $row) {
            $realLine++;

            if ($index === 0) continue; // sauter l'entête

            // 🔍 Vérifie si la ligne est entièrement vide
            $isEmpty = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                continue; // Ignore totalement la ligne vide
            }

            // 🛡️ Vérifie le nombre de colonnes
            if (count($row) < 22) {
                $msg = "Ligne $realLine ignorée : colonnes insuffisantes (" . count($row) . ")";
                \Log::warning($msg);
                $ignoredRows[] = $msg;
                continue;
            }

            // --- Extraction propre ---
            [
                $bureau,
                $employe_fullname,
                $date_mouvement,
                $fournisseur,
                $compte,
                $type_immo,
                $designation,
                $isVehicule,
                $vehicule,
                $code,
                $groupe_type_immo,
                $sous_type_immo,
                $duree_amorti,
                $etat,
                $taux_ammortissement,
                $duree_ammortissement,
                $date_acquisition,
                $date_mise_en_service,
                $observation,
                $status_immo,
                $montant_ttc,
                $reference_estampillonnage
            ] = array_map(fn($v) => trim((string)$v), $row);

            // 🔎 Vérif doublon (isdeleted = false)
            $immobilisationExistante = Immobilisation::where(function ($query) use ($code, $designation) {
                $query->where('code', $code)
                    ->orWhere('designation', $designation);
            })
            ->where('isdeleted', false)
            ->first();

            if ($immobilisationExistante) {
                $msg = "Ligne $realLine ignorée : immobilisation avec code '$code' ou désignation '$designation' déjà active.";
                \Log::info($msg);
                $ignoredRows[] = $msg;
                continue;
            }

            // --- Relations liées ---
            $bureau_id = Bureau::firstOrCreate(['libelle_bureau' => $bureau]);
            $fournisseur_id = Fournisseur::firstOrCreate(['nom' => $fournisseur]);
            $type_immo_id = TypeImmo::firstOrCreate(['libelle_typeImmo' => $type_immo, 'compte' => $compte])->id;

            if (empty($groupe_type_immo)) {
                $msg = "Ligne $realLine ignorée : groupe type immo vide.";
                \Log::warning($msg);
                $ignoredRows[] = $msg;
                continue;
            }

            $id_groupe_type_immo = GroupeTypeImmo::firstOrCreate([
                'libelle' => $groupe_type_immo,
                'compte' => $compte
            ]);

            $id_sous_type_immo = SousTypeImmo::firstOrCreate([
                'libelle' => $sous_type_immo,
                'compte' => $compte,
                'id_type_immo' => $type_immo_id
            ]);

            $id_status_immo = StatusImmo::firstOrCreate(['libelle_status_immo' => $status_immo]);

            // 👤 Employé
            $employe = null;
            if (!empty($employe_fullname)) {
                $parts = preg_split('/\s+/', trim($employe_fullname));
                $nom = array_shift($parts);
                $prenom = implode(' ', $parts);
                if (!empty($nom) && !empty($prenom)) {
                    $employe = Employe::firstOrCreate(['nom' => $nom, 'prenom' => $prenom]);
                }
            }

            // 🗓️ Formats de dates automatiques
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y'];
            $formatDate = fn($date) => collect($formats)
                ->map(fn($fmt) => \DateTime::createFromFormat($fmt, $date))
                ->filter()
                ->first()?->format('Y-m-d');

            $date_mouvement_formatee = $formatDate($date_mouvement);
            $date_acquisition_formatee = $formatDate($date_acquisition);
            $date_mise_en_service_formatee = $formatDate($date_mise_en_service);

            if (!$date_mouvement_formatee || !$date_acquisition_formatee || !$date_mise_en_service_formatee) {
                $msg = "Ligne $realLine ignorée : une ou plusieurs dates invalides.";
                \Log::warning($msg);
                $ignoredRows[] = $msg;
                continue;
            }

            // ✅ Insertion
            Immobilisation::create([
                'bureau_id' => $bureau_id->id,
                'employe_id' => $employe?->id,
                'date_mouvement' => $date_mouvement_formatee,
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
                'duree_ammortissement' => round($duree_ammortissement),
                'date_acquisition' => $date_acquisition_formatee,
                'date_mise_en_service' => $date_mise_en_service_formatee,
                'observation' => $observation,
                'id_status_immo' => $id_status_immo->id,
                'montant_ttc' => $montant_ttc,
                'reference_estampillonnage' => $reference_estampillonnage,
                'isdeleted' => false,
            ]);

            $importedCount++;
        }

        return response()->json([
            'message' => "Import terminé ! ($importedCount lignes importées)",
            'ignored' => $ignoredRows
        ]);
    }


}
