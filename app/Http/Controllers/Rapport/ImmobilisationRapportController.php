<?php

namespace App\Http\Controllers\Rapport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Immobilisation;
use App\Models\Transfert;
use App\Models\Vehicule;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Fournisseur;
use App\Models\Intervention; // NOUVEAU: Importer le modèle Intervention
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Assurez-vous que c'est bien la façade Pdf et non '\Pdf'
use Illuminate\Pagination\LengthAwarePaginator;

class ImmobilisationRapportController extends Controller
{
    /**
     * Récupère les données pour les rapports (enregistrement, transfert ou intervention) en fonction des filtres.
     */
    public function getRapportData(Request $request)
    {
        $typeRapport = $request->input('id_type_rapport');
            $data = null;
            $message = '';
            $success = true;

            // Validation commune pour le type de rapport
            $validator = Validator::make($request->all(), [
                'id_type_rapport' => 'required|string',
            ]);

            if ($validator->fails()) {
                return new PostResource(false, 'Type de rapport manquant.', ['errors' => $validator->errors()]);
            }

            switch ($typeRapport) {
            case 'enregistrement':
                // Validation (inchangée)
                $validator = Validator::make($request->all(), [
                    'code_immo' => 'nullable|string',
                    'date_debut_acquisition' => 'nullable|date',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport d\'enregistrement.', [
                        'errors' => $validator->errors()
                    ]);
                }

                $codeImmo = $request->input('code_immo');
                $dateDebutAcquisition = $request->input('date_debut_acquisition');

                // -------------------------------------------------------------
                // 1. Requête pour les IMMOBILISATIONS (avec pagination standard)
                // -------------------------------------------------------------
                $immoQuery = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ]);

                if ($request->filled('code_immo')) {
                    $immoQuery->where('code', 'like', '%' . $codeImmo . '%');
                }

                if ($request->filled('date_debut_acquisition')) {
                    $immoQuery->whereDate('date_acquisition', '>=', $dateDebutAcquisition);
                }

                // PAGINATION STANDARD appliquée aux Immobilisations
                $dataImmoPaginated = $immoQuery->latest()->paginate(100);

                // -------------------------------------------------------------
                // 2. Requête pour les VEHICULES (sans pagination, filtrés)
                // -------------------------------------------------------------
                $vehiculeQuery = Vehicule::with([
                    'modele', 'marque', 'sousTypeImmo', 'groupeTypeImmo', 'employe', 'bureau', 'fournisseur'
                ])->where('isdeleted', false);

                if ($request->filled('code_immo')) {
                    $vehiculeQuery->where('code', 'like', '%' . $codeImmo . '%');
                }

                if ($request->filled('date_debut_acquisition')) {
                    $vehiculeQuery->whereDate('date_acquisition', '>=', $dateDebutAcquisition);
                }

                // On récupère TOUS les véhicules filtrés (sans pagination)
                $dataVehicule = $vehiculeQuery->get();

                // -------------------------------------------------------------
                // 3. Normalisation et Fusion par Tableaux Bruts
                // -------------------------------------------------------------

                // 3a. Normaliser les Immobilisations et ajouter le type d'actif
                $immoArray = $dataImmoPaginated->getCollection()->map(function($immo) {
                    $data = $immo->toArray();
                    $data['type_actif'] = 'Immobilisation';
                    // Mettre à null les champs spécifiques aux véhicules pour éviter les N/A
                    $data['immatriculation'] = null;
                    $data['numero_chassis'] = null;
                    return $data;
                })->toArray();


                // 3b. Normaliser les Véhicules: CRÉER LE CHAMP 'designation' = "Marque - Modèle"
                $vehiculeArray = $dataVehicule->map(function($vehicule) {
                    $data = $vehicule->toArray();
                    $data['type_actif'] = 'Vehicule';

                    // Construction de la désignation pour la colonne commune du frontend
                    $marque = optional($vehicule->marque)->libelle ?? 'N/A';
                    $modele = optional($vehicule->modele)->libelle_modele ?? 'N/A';
                    $data['designation'] = $marque . ' - ' . $modele;

                    return $data;
                })->toArray();

                // 3c. Fusionner les deux tableaux
                $mergedArray = array_merge($immoArray, $vehiculeArray);
                $mergedCollection = collect($mergedArray);

                // 3d. Trier et réindexer la collection fusionnée
                $sortedMergedCollection = $mergedCollection->sortByDesc('created_at')->values();

                // 3e. Mettre à jour l'objet de pagination
                $dataImmoPaginated->setCollection($sortedMergedCollection);

                // Renvoyer l'objet paginé mis à jour
                $data = $dataImmoPaginated;
                $message = 'Rapport combiné Immobilisations/Véhicules généré avec succès.';
                break;

            case 'transfert':
                // Validation spécifique pour le rapport de transfert (dates obligatoires)
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'old_bureau_id' => 'nullable|exists:bureaus,id',
                    'bureau_id' => 'nullable|exists:bureaus,id',
                    'old_employe_id' => 'nullable|exists:employes,id',
                    'employe_id' => 'nullable|exists:employes,id',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour les dates de transfert.', [
                        'errors' => $validator->errors()
                    ]);
                }

                $query = Transfert::with([
                    'immobilisation',
                    'old_bureau',
                    'bureau',
                    'old_employe',
                    'employe',
                ])->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

                if ($request->filled('old_bureau_id')) {
                    $query->where('old_bureau_id', $request->old_bureau_id);
                }
                if ($request->filled('bureau_id')) {
                    $query->where('bureau_id', $request->bureau_id);
                }
                if ($request->filled('old_employe_id')) {
                    $query->where('old_employe_id', $request->old_employe_id);
                }
                if ($request->filled('employe_id')) {
                    $query->where('employe_id', $request->employe_id);
                }

                $data = $query->latest()->paginate(100);
                $message = 'Rapport de transferts d\'immobilisations généré avec succès.';
                break;

            case 'intervention': // NOUVEAU: Logique pour les rapports d'intervention
                // Validation spécifique pour le rapport d'intervention (dates obligatoires)
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'type_intervention_id' => 'nullable|exists:type_interventions,id',
                    'immo_id' => 'nullable|exists:immobilisations,id',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour les dates d\'intervention.', [
                        'errors' => $validator->errors()
                    ]);
                }

                $query = Intervention::with([
                    'typeIntervention',
                    'immobilisation',
                ])->whereBetween('date_intervention', [$request->date_debut, $request->date_fin]);

                if ($request->filled('type_intervention_id')) {
                    $query->where('type_intervention_id', $request->type_intervention_id);
                }

                if ($request->filled('immo_id')) {
                    $query->where('immo_id', $request->immo_id);
                }

                $data = $query->latest()->paginate(100);
                $message = 'Rapport des interventions sur immobilisations généré avec succès.';
                break;

            case 'inventaire':
                // Validation spécifique pour la fiche d'inventaire (dates d'acquisition obligatoires)
                $validator = Validator::make($request->all(), [
                    'date_debut_acquisition' => 'required|date',
                    'date_fin_acquisition' => 'required|date|after_or_equal:date_debut_acquisition',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour la fiche d\'inventaire. Les dates d\'acquisition sont obligatoires.', [
                        'errors' => $validator->errors()
                    ]);
                }

                $dateDebut = $request->input('date_debut_acquisition');
                $dateFin = $request->input('date_fin_acquisition');

                // -------------------------------------------------------------
                // 1. Requête IMMOBILISATIONS (Base de l'objet paginé)
                // -------------------------------------------------------------
                $immoQuery = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ]);
                $immoQuery->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
                $dataImmoPaginated = $immoQuery->latest()->paginate(100);

                // -------------------------------------------------------------
                // 2. Requête VEHICULES (Collection complète filtrée)
                // -------------------------------------------------------------
                $vehiculeQuery = Vehicule::with([
                    'modele', 'marque', 'sousTypeImmo', 'groupeTypeImmo', 'employe', 'bureau', 'fournisseur'
                ])->where('isdeleted', false);
                $vehiculeQuery->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
                $dataVehicule = $vehiculeQuery->get();

                // -------------------------------------------------------------
                // 3. Normalisation et Fusion par Tableaux Bruts
                // -------------------------------------------------------------

                // 3a. Normaliser les Immobilisations et ajouter le type d'actif
                $immoArray = $dataImmoPaginated->getCollection()->map(function($immo) {
                    $data = $immo->toArray();
                    $data['type_actif'] = 'Immobilisation';
                    // Mettre à null les champs spécifiques aux véhicules
                    $data['immatriculation'] = null;
                    $data['numero_chassis'] = null;
                    return $data;
                })->toArray();


                // 3b. Normaliser les Véhicules: CRÉER LE CHAMP 'designation'
                $vehiculeArray = $dataVehicule->map(function($vehicule) {
                    $data = $vehicule->toArray();
                    $data['type_actif'] = 'Vehicule';

                    // **FIX** : Créer le champ 'designation' à partir de la marque et du modèle
                    $marque = optional($vehicule->marque)->libelle ?? 'N/A';
                    $modele = optional($vehicule->modele)->libelle_modele ?? 'N/A';

                    // Construction de la désignation pour l'affichage dans la colonne commune
                    $data['designation'] = $marque . ' - ' . $modele;

                    // Mettre à null les champs spécifiques aux Immos pour l'affichage (si besoin)
                    // e.g. si le champ 'etat' est différent pour les deux modèles et que ça cause N/A

                    return $data;
                })->toArray();

                // 3c. Fusionner les deux tableaux
                $mergedArray = array_merge($immoArray, $vehiculeArray);
                $mergedCollection = collect($mergedArray);

                // 3d. Trier et réindexer la collection fusionnée
                $sortedMergedCollection = $mergedCollection->sortByDesc('created_at')->values();

                // 3e. Mettre à jour l'objet de pagination
                $dataImmoPaginated->setCollection($sortedMergedCollection);

                $data = $dataImmoPaginated;
                $message = 'Fiche d\'inventaire combinée Immobilisations/Véhicules générée avec succès.';
                break;

            case 'bureau':
                // Validation spécifique pour le rapport par bureau
                $validator = Validator::make($request->all(), [
                    'date_debut_bureau' => 'required|date',
                    'date_fin_bureau' => 'required|date|after_or_equal:date_debut_bureau',
                    'bureau_id' => 'nullable|exists:bureaus,id',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport par bureau. Les dates sont obligatoires.', [
                        'errors' => $validator->errors()
                    ]);
                }

                $query = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ])
                ->whereBetween('date_acquisition', [$request->date_debut_bureau, $request->date_fin_bureau]);

                if ($request->filled('bureau_id')) {
                    $query->where('bureau_id', $request->bureau_id);
                }

                $data = $query->latest()->paginate(100);
                $message = 'Rapport des immobilisations par bureau généré avec succès.';
                break;

            default:
                $success = false;
                $message = 'Type de rapport non valide.';
                $data = [];
                break;
        }

        return new PostResource($success, $message, $data);
    }

    /**
     * Génère le PDF pour les rapports (enregistrement, transfert ou intervention) en fonction des filtres.
     */
    public function imprimerRapportData(Request $request)
    {
        $typeRapport = $request->input('id_type_rapport');
        $data = null;
        $viewName = '';
        $filename = '';
        $compactData = []; // Initialiser $compactData

        // Validation commune pour le type de rapport
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Type de rapport manquant pour l\'impression.'], 400);
        }

        switch ($typeRapport) {
            case 'enregistrement':
            // 1. Validation spécifique
            $validator = Validator::make($request->all(), [
                'code_immo' => 'nullable|string',
                'date_debut_acquisition' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation échouée pour le rapport d\'enregistrement PDF.', 'errors' => $validator->errors()], 422);
            }

            $codeImmo = $request->input('code_immo');
            $dateDebutAcquisition = $request->input('date_debut_acquisition');

            // --- A. Requête IMMOBILISATIONS (Collection complète) ---
            $immoQuery = Immobilisation::with([
                'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                'employe', 'bureau', 'fournisseur'
            ]);

            if ($request->filled('code_immo')) {
                $immoQuery->where('code', 'like', '%' . $codeImmo . '%');
            }
            if ($request->filled('date_debut_acquisition')) {
                $immoQuery->whereDate('date_acquisition', '>=', $dateDebutAcquisition);
            }
            $dataImmo = $immoQuery->latest()->get(); // Pas de pagination

            // --- B. Requête VEHICULES (Collection complète) ---
            $vehiculeQuery = Vehicule::with([
                'modele', 'marque', 'sousTypeImmo', 'groupeTypeImmo', 'employe', 'bureau', 'fournisseur'
            ])->where('isdeleted', false);

            if ($request->filled('code_immo')) {
                $vehiculeQuery->where('code', 'like', '%' . $codeImmo . '%');
            }
            if ($request->filled('date_debut_acquisition')) {
                $vehiculeQuery->whereDate('date_acquisition', '>=', $dateDebutAcquisition);
            }
            $dataVehicule = $vehiculeQuery->get();

            // --- C. Normalisation et Fusion (LOGIQUE CLÉ) ---

            // Normaliser les Immobilisations
            $immoArray = $dataImmo->map(function($immo) {
                $data = $immo->toArray();
                $data['type_actif'] = 'Immobilisation';
                $data['immatriculation'] = null;
                $data['numero_chassis'] = null;
                return $data;
            }); // Ne pas faire ->toArray() tout de suite

            // Normaliser les Véhicules
            $vehiculeArray = $dataVehicule->map(function($vehicule) {
                $data = $vehicule->toArray();
                $data['type_actif'] = 'Vehicule';
                $marque = optional($vehicule->marque)->libelle ?? 'N/A';
                $modele = optional($vehicule->modele)->libelle_modele ?? 'N/A';
                $data['designation'] = $marque . ' - ' . $modele;
                return $data;
            }); // Ne pas faire ->toArray() tout de suite

            // Fusionner, trier et définir la variable $data finale
            $data = $immoArray->merge($vehiculeArray)->sortByDesc('created_at')->values();

            // --- D. Définition des variables finales pour l'impression ---
            $viewName = 'pdf.rapport.rapport_immobilisations'; // Adaptez le nom de la vue
            $filename = 'rapport_enregistrement_immobilisations.pdf';
            $compactData = ['immobilisations' => $data]; // Utilisation de $data pour la collection unifiée
            break;

            case 'transfert':
                // Validation spécifique pour l'impression du rapport de transfert (dates obligatoires)
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'old_bureau_id' => 'nullable|exists:bureaus,id',
                    'bureau_id' => 'nullable|exists:bureaus,id',
                    'old_employe_id' => 'nullable|exists:employes,id',
                    'employe_id' => 'nullable|exists:employes,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Les dates de début et de fin sont obligatoires et valides pour le rapport de transfert.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $query = Transfert::with([
                    'immobilisation',
                    'old_bureau',
                    'bureau',
                    'old_employe',
                    'employe',
                ])->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

                if ($request->filled('old_bureau_id')) {
                    $query->where('old_bureau_id', $request->old_bureau_id);
                }
                if ($request->filled('bureau_id')) {
                    $query->where('bureau_id', $request->bureau_id);
                }
                if ($request->filled('old_employe_id')) {
                    $query->where('old_employe_id', $request->old_employe_id);
                }
                if ($request->filled('employe_id')) {
                    $query->where('employe_id', $request->employe_id);
                }

                $data = $query->latest()->get(); // Pas de pagination pour le PDF
                $viewName = 'pdf.rapport.rapport_transferts'; // Chemin de la vue pour les transferts
                $filename = 'rapport_transferts_immobilisations.pdf';
                $compactData = ['transferts' => $data]; // Définir les données pour la vue
                break;

            case 'intervention': // NOUVEAU: Logique pour les rapports d'intervention
                // Validation spécifique pour l'impression du rapport d'intervention (dates obligatoires)
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'type_intervention_id' => 'nullable|exists:type_interventions,id',
                    'immo_id' => 'nullable|exists:immobilisations,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Les dates de début et de fin sont obligatoires et valides pour le rapport d\'intervention.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $query = Intervention::with([
                    'typeIntervention',
                    'immobilisation',
                ])->whereBetween('date_intervention', [$request->date_debut, $request->date_fin]);

                if ($request->filled('type_intervention_id')) {
                    $query->where('type_intervention_id', $request->type_intervention_id);
                }

                if ($request->filled('immo_id')) {
                    $query->where('immo_id', $request->immo_id);
                }

                $data = $query->latest()->get(); // Pas de pagination pour le PDF
                $viewName = 'pdf.rapport.rapport_interventions'; // Chemin de la vue pour les interventions
                $filename = 'rapport_interventions_immobilisations.pdf';
                $compactData = ['interventions' => $data]; // Définir les données pour la vue
                break;

            case 'inventaire':
            // 2. Validation spécifique
            $validator = Validator::make($request->all(), [
                'date_debut_acquisition' => 'required|date',
                'date_fin_acquisition' => 'required|date|after_or_equal:date_debut_acquisition',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation échouée pour la fiche d\'inventaire PDF. Les dates d\'acquisition sont obligatoires.', 'errors' => $validator->errors()], 422);
            }

            $dateDebut = $request->input('date_debut_acquisition');
            $dateFin = $request->input('date_fin_acquisition');

            // --- A. Requête IMMOBILISATIONS (Collection complète) ---
            $immoQuery = Immobilisation::with([
                'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                'employe', 'bureau', 'fournisseur'
            ])->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
            $dataImmo = $immoQuery->latest()->get(); // Pas de pagination

            // --- B. Requête VEHICULES (Collection complète) ---
            $vehiculeQuery = Vehicule::with([
                'modele', 'marque', 'sousTypeImmo', 'groupeTypeImmo', 'employe', 'bureau', 'fournisseur'
            ])->where('isdeleted', false)
              ->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
            $dataVehicule = $vehiculeQuery->get();

            // --- C. Normalisation et Fusion (LOGIQUE CLÉ) ---

            // Normaliser les Immobilisations
            $immoArray = $dataImmo->map(function($immo) {
                $data = $immo->toArray();
                $data['type_actif'] = 'Immobilisation';
                $data['immatriculation'] = null;
                $data['numero_chassis'] = null;
                return $data;
            });

            // Normaliser les Véhicules
            $vehiculeArray = $dataVehicule->map(function($vehicule) {
                $data = $vehicule->toArray();
                $data['type_actif'] = 'Vehicule';
                $marque = optional($vehicule->marque)->libelle ?? 'N/A';
                $modele = optional($vehicule->modele)->libelle_modele ?? 'N/A';
                $data['designation'] = $marque . ' - ' . $modele;
                return $data;
            });

            // Fusionner, trier et définir la variable $data finale
            $data = $immoArray->merge($vehiculeArray)->sortByDesc('created_at')->values();

            // --- D. Définition des variables finales pour l'impression ---
            $viewName = 'pdf.rapport.fiche_inventaire'; // Adaptez le nom de la vue
            $filename = 'fiche_inventaire_immobilisations.pdf';
            $compactData = ['immobilisations' => $data]; // Utilisation de $data pour la collection unifiée
            break;

            case 'bureau': // NOUVEAU: Logique pour le rapport par bureau (PDF)
                // Validation spécifique pour l'impression du rapport par bureau
                $validator = Validator::make($request->all(), [
                    'date_debut_bureau' => 'required|date',
                    'date_fin_bureau' => 'required|date|after_or_equal:date_debut_bureau',
                    'bureau_id' => 'nullable|exists:bureaus,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Les dates de début et de fin sont obligatoires pour le rapport par bureau.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $query = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ])->whereBetween('date_acquisition', [$request->date_debut_bureau, $request->date_fin_bureau]);

                if ($request->filled('bureau_id')) {
                    $query->where('bureau_id', $request->bureau_id);
                }

                $data = $query->latest()->get(); // Pas de pagination pour le PDF
                $viewName = 'pdf.rapport.rapport_bureau'; // <-- Chemin de la vue pour le rapport bureau
                $filename = 'rapport_immobilisations_par_bureau.pdf';
                $compactData = ['immobilisations' => $data]; // Données passées à la vue
                break;




            default:
                return response()->json(['success' => false, 'message' => 'Type de rapport non valide pour l\'impression.'], 400);
        }

        // S'assurer que $compactData est défini avant d'appeler loadView
        if (empty($compactData)) {
            // Cela ne devrait pas arriver si tous les cases sont couverts, mais c'est une sécurité
            return response()->json(['success' => false, 'message' => 'Erreur interne: Données du rapport non préparées pour la vue PDF.'], 500);
        }

        $pdf = \Pdf::loadView($viewName, $compactData);

        return $pdf->download($filename);
    }

/*     public function getCodesImmoEtVehicule(Request $request)
    {
        try {
            // 1. Récupérer les codes des immobilisations avec le champ 'type'
            $codesImmo = Immobilisation::select('id', 'code')
                ->addSelect(\DB::raw("'immobilisation' as type")) // Ajoute le type 'immobilisation'
                ->get();

            // 2. Récupérer les codes des véhicules avec le champ 'type'
            $codesVehicule = Vehicule::select('id', 'code')
                ->where('isdeleted', false)
                ->addSelect(\DB::raw("'vehicule' as type")) // Ajoute le type 'vehicule'
                ->get();

            // 3. Fusionner les deux collections sans écraser (méthode concat)
            $codesCombinés = $codesImmo->concat($codesVehicule);

            // Renvoyer la collection combinée
            $result = [
                'codes' => $codesCombinés->values()
            ];

            return new PostResource(true, 'Liste combinée des codes récupérée avec succès.', $result);

        } catch (\Exception $e) {
            return new PostResource(false, 'Erreur lors de la récupération des codes : ' . $e->getMessage());
        }
    } */

    /* public function getCodesImmoEtVehicule(Request $request)
    {
    try {
        // --- 1. Requête Immobilisations : Code et Designation ---
        $codesImmo = Immobilisation::select('id', 'code', 'designation')
            ->addSelect(\DB::raw("'immobilisation' as type"))
            ->addSelect(\DB::raw('designation as designation_complete')) 
            ->get(); // Récupère la collection de modèles (avec les champs ajoutés)
        
        // Convertir la collection en un tableau PHP brut et conserver uniquement les colonnes nécessaires
        $immoArray = $codesImmo->map(function ($immo) {
            return [
                'id' => $immo->id,
                'code' => $immo->code,
                'type' => $immo->type,
                'designation_complete' => $immo->designation_complete,
            ];
        })->toArray(); // <-- Conversion en tableau PHP brut


        // --- 2. Requête Véhicules : Code, Marque et Modèle pour construire la Designation ---
        $codesVehicule = Vehicule::with(['marque', 'modele']) 
            ->select('id', 'code', 'marque_id', 'modele_id')
            ->where('isdeleted', false)
            ->addSelect(\DB::raw("'vehicule' as type"))
            ->get();
        
        // 3. Transformation des Véhicules et Fusion

        // Transformer les véhicules et créer le tableau PHP brut
        $vehiculeArray = $codesVehicule->map(function($vehicule) {
            
            $marque = optional($vehicule->marque)->libelle ?? 'N/A';
            $modele = optional($vehicule->modele)->libelle_modele ?? 'N/A';
            $designationComplete = $marque . ' - ' . $modele;

            return [
                'id' => $vehicule->id,
                'code' => $vehicule->code,
                'type' => $vehicule->type,
                'designation_complete' => $designationComplete 
            ];
        })->toArray(); // <-- Conversion en tableau PHP brut

        // 3b. Fusionner les deux tableaux bruts et reconvertir en Collection Laravel
        $mergedArray = array_merge($immoArray, $vehiculeArray);
        $codesCombinés = collect($mergedArray); // On reconvertit en collection pour l'envoi final

        // 4. Renvoyer la collection combinée
        $result = [
            'codes' => $codesCombinés->values()
        ];

        return new PostResource(true, 'Liste combinée des codes/désignations récupérée avec succès.', $result);

        } catch (\Exception $e) {
            return new PostResource(false, 'Erreur lors de la récupération des codes : ' . $e->getMessage());
        }
    } */

        public function getCodesImmoEtVehicule(Request $request)
{
    try {
        // --- 1. Requête Immobilisations : Code et Designation ---
        // CORRIGÉ : On sélectionne 'designation' en tant qu'alias 'designation_complete'
        $codesImmo = Immobilisation::select('id', 'code')
            ->selectRaw('designation as designation_complete') // Utilisation de selectRaw pour l'alias
            ->addSelect(\DB::raw("'immobilisation' as type"))
            ->get(); // Récupère la collection de modèles (avec les champs ajoutés)
        
        // Convertir la collection en un tableau PHP brut. 
        // L'alias 'designation_complete' est maintenant directement accessible et correct.
        $immoArray = $codesImmo->map(function ($immo) {
            return [
                'id' => $immo->id,
                'code' => $immo->code,
                'type' => $immo->type,
                // On utilise l'alias correctement matérialisé
                'designation_complete' => $immo->designation_complete, 
            ];
        })->toArray(); // <-- Conversion en tableau PHP brut


        // --- 2. Requête Véhicules : Code, Marque et Modèle pour construire la Designation ---
        $codesVehicule = Vehicule::with(['marque', 'modele']) 
            ->select('id', 'code', 'marque_id', 'modele_id')
            ->where('isdeleted', false)
            ->addSelect(\DB::raw("'vehicule' as type"))
            ->get();
        
        // 3. Transformation des Véhicules et Fusion

        // Transformer les véhicules et créer le tableau PHP brut
        $vehiculeArray = $codesVehicule->map(function($vehicule) {
            
            $marque = optional($vehicule->marque)->libelle ?? 'N/A';
            $modele = optional($vehicule->modele)->libelle_modele ?? 'N/A';
            $designationComplete = $marque . ' - ' . $modele;

            return [
                'id' => $vehicule->id,
                'code' => $vehicule->code,
                'type' => $vehicule->type,
                // Le nom de la clé est uniforme ici aussi
                'designation_complete' => $designationComplete 
            ];
        })->toArray(); // <-- Conversion en tableau PHP brut

        // 3b. Fusionner les deux tableaux bruts et reconvertir en Collection Laravel
        $mergedArray = array_merge($immoArray, $vehiculeArray);
        $codesCombinés = collect($mergedArray); // On reconvertit en collection pour l'envoi final

        // 4. Renvoyer la collection combinée
        $result = [
            'codes' => $codesCombinés->values()
        ];

        return new PostResource(true, 'Liste combinée des codes/désignations récupérée avec succès.', $result);

    } catch (\Exception $e) {
        return new PostResource(false, 'Erreur lors de la récupération des codes : ' . $e->getMessage());
    }
}
}
