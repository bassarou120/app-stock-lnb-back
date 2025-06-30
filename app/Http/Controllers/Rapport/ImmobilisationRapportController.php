<?php

namespace App\Http\Controllers\Rapport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Immobilisation;
use App\Models\Transfert;
use App\Models\Intervention; // NOUVEAU: Importer le modèle Intervention
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Assurez-vous que c'est bien la façade Pdf et non '\Pdf'

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
                // Validation spécifique pour le rapport d'enregistrement
                $validator = Validator::make($request->all(), [
                    'code_immo' => 'nullable|string',
                    'date_debut_acquisition' => 'nullable|date',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport d\'enregistrement.', ['errors' => $validator->errors()]);
                }

                $query = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ]);

                if ($request->filled('code_immo')) {
                    $query->where('code', 'like', '%' . $request->input('code_immo') . '%');
                }

                if ($request->filled('date_debut_acquisition')) {
                    $query->whereDate('date_acquisition', '>=', $request->input('date_debut_acquisition'));
                }

                $data = $query->latest()->paginate(100);
                $message = 'Rapport d\'enregistrement des immobilisations généré avec succès.';
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

            case 'inventaire': // <-- NOUVEAU CASE POUR LA FICHE D'INVENTAIRE
                // Validation spécifique pour la fiche d'inventaire (dates d'acquisition obligatoires)
                $validator = Validator::make($request->all(), [
                    'date_debut_acquisition' => 'required|date',
                    'date_fin_acquisition' => 'required|date|after_or_equal:date_debut_acquisition',
                    // Aucun autre filtre attendu pour l'inventaire simple, selon la discussion
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour la fiche d\'inventaire. Les dates d\'acquisition sont obligatoires.', [
                        'errors' => $validator->errors()
                    ]);
                }

                $query = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ])->whereBetween('date_acquisition', [$request->date_debut_acquisition, $request->date_fin_acquisition]);

                $data = $query->latest()->paginate(100);
                $message = 'Fiche d\'inventaire des immobilisations générée avec succès.';
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
                // Validation spécifique pour l'impression du rapport d'enregistrement
                $validator = Validator::make($request->all(), [
                    'code_immo' => 'nullable|string',
                    'date_debut_acquisition' => 'nullable|date',
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => 'Validation échouée pour le rapport d\'enregistrement PDF.', 'errors' => $validator->errors()], 422);
                }

                $query = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ]);

                if ($request->filled('code_immo')) {
                    $query->where('code', 'like', '%' . $request->input('code_immo') . '%');
                }

                if ($request->filled('date_debut_acquisition')) {
                    $query->whereDate('date_acquisition', '>=', $request->input('date_debut_acquisition'));
                }

                $data = $query->latest()->get(); // Pas de pagination pour le PDF
                $viewName = 'pdf.rapport.rapport_immobilisations'; // Chemin de la vue pour l'enregistrement
                $filename = 'rapport_enregistrement_immobilisations.pdf';
                $compactData = ['immobilisations' => $data]; // Définir les données pour la vue
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

            case 'inventaire': // <-- NOUVEAU CASE POUR LA FICHE D'INVENTAIRE PDF
                // Validation spécifique pour l'impression de la fiche d'inventaire
                $validator = Validator::make($request->all(), [
                    'date_debut_acquisition' => 'required|date',
                    'date_fin_acquisition' => 'required|date|after_or_equal:date_debut_acquisition',
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => 'Validation échouée pour la fiche d\'inventaire PDF. Les dates d\'acquisition sont obligatoires.', 'errors' => $validator->errors()], 422);
                }

                $query = Immobilisation::with([
                    'vehicule', 'groupeTypeImmo', 'sousTypeImmo', 'statusImmo',
                    'employe', 'bureau', 'fournisseur'
                ])->whereBetween('date_acquisition', [$request->date_debut_acquisition, $request->date_fin_acquisition]);

                $data = $query->latest()->get(); // Pas de pagination pour le PDF
                $viewName = 'pdf.rapport.fiche_inventaire'; // <-- NOUVEAU Chemin de la vue pour la fiche d'inventaire
                $filename = 'fiche_inventaire_immobilisations.pdf';
                $compactData = ['immobilisations' => $data]; // Définir les données pour la vue (ce sera une liste d'immobilisations)
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
}
