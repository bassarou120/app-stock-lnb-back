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
                case 'inventaire':
                    case 'enregistrement':
                        // Validation des dates optionnelles pour filtrage
                        $validator = Validator::make($request->all(), [
                            'date_debut_acquisition' => 'nullable|date',
                            'date_fin_acquisition' => 'nullable|date|after_or_equal:date_debut_acquisition',
                            'code_immo' => 'nullable|string',
                        ]);
            
                        if ($validator->fails()) {
                            return new PostResource(false, 'Validation échouée.', ['errors' => $validator->errors()]);
                        }
            
                        $dateDebut = $request->input('date_debut_acquisition');
                        $dateFin = $request->input('date_fin_acquisition');
                        $codeImmo = $request->input('code_immo');
            
                        // 1. Récupérer les immobilisations
                        $immoQuery = Immobilisation::with(['statusImmo', 'groupeTypeImmo', 'sousTypeImmo']);
                        if ($dateDebut && $dateFin) {
                            $immoQuery->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
                        }
                        if ($codeImmo) {
                            $immoQuery->where('code', 'like', '%' . $codeImmo . '%');
                        }
                        $dataImmo = $immoQuery->get();
            
                        // 2. Récupérer les véhicules
                        $vehiculeQuery = Vehicule::with(['statusImmo', 'groupeTypeImmo', 'sousTypeImmo'])
                                                 ->where('isdeleted', false);
                        if ($dateDebut && $dateFin) {
                            $vehiculeQuery->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
                        }
                        if ($codeImmo) {
                            $vehiculeQuery->where('code', 'like', '%' . $codeImmo . '%');
                        }
                        $dataVehicule = $vehiculeQuery->get();
            
                        // 3. Normaliser les données pour uniformiser les colonnes
                        $immoArray = $dataImmo->map(function($item){
                            return [
                                'id' => $item->id,
                                'type_actif' => 'Immobilisation',
                                'code' => $item->code,
                                'designation' => $item->designation ?? $item->code,
                                'etat' => $item->etat ?? 'N/A',
                                'status_immo' => $item->statusImmo ? ['libelle_status_immo' => $item->statusImmo->libelle_status_immo] : null,
                                'groupe_type_immo' => $item->groupeTypeImmo,
                                'sous_type_immo' => $item->sousTypeImmo,
                                'montant_ttc' => $item->montant_ttc,
                                'date_acquisition' => $item->date_acquisition,
                            ];
                        });
            
                        $vehiculeArray = $dataVehicule->map(function($item){
                            return [
                                'id' => $item->id,
                                'type_actif' => 'Vehicule',
                                'code' => $item->code,
                                'designation' => optional($item->marque)->libelle . ' - ' . optional($item->modele)->libelle_modele,
                                'etat' => $item->etat ?? 'N/A',
                                'status_immo' => $item->statusImmo ? ['libelle_status_immo' => $item->statusImmo->libelle_status_immo] : null,
                                'groupe_type_immo' => $item->groupeTypeImmo,
                                'montant_ttc' => $item->montant_ttc,
                                'sous_type_immo' => $item->sousTypeImmo,
                                'date_acquisition' => $item->date_acquisition,
                            ];
                        });
            
                        // 4. Fusionner et trier
                        $merged = $immoArray->merge($vehiculeArray)->sortByDesc('date_acquisition')->values();
            
                        // 5. Retourner les données paginées (optionnel)
                        $perPage = 100;
                        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
                        $currentItems = $merged->slice(($page - 1) * $perPage, $perPage)->values();
                    
                        $data = new \Illuminate\Pagination\LengthAwarePaginator(
                            $currentItems->toArray(), // <-- Convertir les objets Eloquent de la page en Array
                            $merged->count(), 
                            $perPage, 
                            $page, 
                            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
                        );
            
                        $message = 'Rapport combiné Immobilisations / Véhicules généré avec succès.';
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
        $viewName = '';
        $filename = '';
        $compactData = [];
    
        // Validation commune
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Type de rapport manquant pour l\'impression.'], 400);
        }
    
        // ===========================================================
        // RAPPORTS QUI UTILISENT LA FUSION IMMOBILISATIONS + VEHICULES
        // ===========================================================
        if (in_array($typeRapport, ['enregistrement', 'inventaire'])) {
            $dateDebut = null;
            $dateFin = null;
    
            if ($typeRapport === 'inventaire') {
                $validator = Validator::make($request->all(), [
                    'date_debut_acquisition' => 'required|date',
                    'date_fin_acquisition' => 'required|date|after_or_equal:date_debut_acquisition',
                ]);
                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => 'Validation échouée.', 'errors' => $validator->errors()], 422);
                }
                $dateDebut = $request->date_debut_acquisition;
                $dateFin = $request->date_fin_acquisition;
            } else {
                $dateDebut = $request->date_debut_acquisition ?? null;
            }
    
            // IMMOBILISATIONS
            $immoQuery = Immobilisation::with(['vehicule','groupeTypeImmo','sousTypeImmo','statusImmo','employe','bureau','fournisseur']);
            if ($dateDebut && $dateFin) {
                $immoQuery->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
            } elseif ($dateDebut) {
                $immoQuery->whereDate('date_acquisition', '>=', $dateDebut);
            }
            if ($request->filled('code_immo')) {
                $immoQuery->where('code', 'like', '%' . $request->code_immo . '%');
            }
            $dataImmo = $immoQuery->latest()->get();
    
            // VEHICULES
            $vehiculeQuery = Vehicule::with(['modele','marque','sousTypeImmo','groupeTypeImmo','employe','bureau','fournisseur'])
                ->where('isdeleted', false);
            if ($dateDebut && $dateFin) {
                $vehiculeQuery->whereBetween('date_acquisition', [$dateDebut, $dateFin]);
            } elseif ($dateDebut) {
                $vehiculeQuery->whereDate('date_acquisition', '>=', $dateDebut);
            }
            if ($request->filled('code_immo')) {
                $vehiculeQuery->where('code', 'like', '%' . $request->code_immo . '%');
            }
            $dataVehicule = $vehiculeQuery->get();
    
            // Normalisation
            $dataImmo->each(function ($immo) {
                $immo->type_actif = 'Immobilisation';
                $immo->designation = $immo->designation ?? $immo->nom ?? $immo->code;
                $immo->libelle_groupe = optional($immo->groupeTypeImmo)->libelle ?? '-';
                $immo->libelle_soustype = optional($immo->sousTypeImmo)->libelle ?? '-';
                $immo->libelle_bureau = optional($immo->bureau)->libelle_bureau ?? '-';
                $immo->nom_fournisseur = optional($immo->fournisseur)->nom ?? '-';
                $immo->etat = $immo->etat ?? 'N/A';
                $immo->affecte_a = optional($immo->employe)->nom ?? '-';
                $immo->observation = $immo->observation ?? '-';
            });
            
            $dataVehicule->each(function ($vehicule) {
                $vehicule->type_actif = 'Vehicule';
                $vehicule->designation = (optional($vehicule->marque)->libelle ?? 'N/A') . ' - ' . (optional($vehicule->modele)->libelle_modele ?? 'N/A');
                $vehicule->libelle_groupe = optional($vehicule->groupeTypeImmo)->libelle ?? '-';
                $vehicule->libelle_soustype = optional($vehicule->sousTypeImmo)->libelle ?? '-';
                $vehicule->libelle_bureau = optional($vehicule->bureau)->libelle_bureau ?? '-';
                $vehicule->nom_fournisseur = optional($vehicule->fournisseur)->nom ?? '-';
                $vehicule->etat = $vehicule->etat ?? 'N/A';
                $vehicule->affecte_a = optional($vehicule->employe)->nom ?? '-';
                $vehicule->observation = $vehicule->observation ?? '-';
            });
    
            // Fusion & tri
            $merged = $dataImmo->merge($dataVehicule)->sortByDesc('date_acquisition')->values();
            $compactData = ['actifs' => $merged];
    
            $viewName = 'pdf.rapport.fiche_inventaire';
            $filename = $typeRapport === 'inventaire' 
                ? 'fiche_inventaire_immobilisations.pdf' 
                : 'rapport_enregistrement_immobilisations.pdf';
        } else {
            // ===========================================================
            // AUTRES TYPES DE RAPPORT
            // ===========================================================
            switch ($typeRapport) {
                case 'transfert':
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
    
                    $query = Transfert::with(['immobilisation','old_bureau','bureau','old_employe','employe'])
                        ->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);
                    if ($request->filled('old_bureau_id')) $query->where('old_bureau_id', $request->old_bureau_id);
                    if ($request->filled('bureau_id')) $query->where('bureau_id', $request->bureau_id);
                    if ($request->filled('old_employe_id')) $query->where('old_employe_id', $request->old_employe_id);
                    if ($request->filled('employe_id')) $query->where('employe_id', $request->employe_id);
    
                    $data = $query->latest()->get();
                    $viewName = 'pdf.rapport.rapport_transferts';
                    $filename = 'rapport_transferts_immobilisations.pdf';
                    $compactData = ['transferts' => $data];
                    break;
    
                case 'intervention':
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
    
                    $query = Intervention::with(['typeIntervention','immobilisation'])
                        ->whereBetween('date_intervention', [$request->date_debut, $request->date_fin]);
                    if ($request->filled('type_intervention_id')) $query->where('type_intervention_id', $request->type_intervention_id);
                    if ($request->filled('immo_id')) $query->where('immo_id', $request->immo_id);
    
                    $data = $query->latest()->get();
                    $viewName = 'pdf.rapport.rapport_interventions';
                    $filename = 'rapport_interventions_immobilisations.pdf';
                    $compactData = ['interventions' => $data];
                    break;
    
                case 'bureau':
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
    
                    $query = Immobilisation::with(['vehicule','groupeTypeImmo','sousTypeImmo','statusImmo','employe','bureau','fournisseur'])
                        ->whereBetween('date_acquisition', [$request->date_debut_bureau, $request->date_fin_bureau]);
                    if ($request->filled('bureau_id')) $query->where('bureau_id', $request->bureau_id);
    
                    $data = $query->latest()->get();
                    $viewName = 'pdf.rapport.rapport_bureau';
                    $filename = 'rapport_immobilisations_par_bureau.pdf';
                    $compactData = ['immobilisations' => $data];
                    break;
    
                default:
                    return response()->json(['success' => false, 'message' => 'Type de rapport non valide pour l\'impression.'], 400);
            }
        }
    
        // Vérification finale
        if (empty($compactData)) {
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
