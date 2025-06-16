<?php

namespace App\Http\Controllers\Rapport; // Assurez-vous que le namespace est correct

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Immobilisation;
use App\Models\Transfert; // NOUVEAU: Importer le modèle Transfert
// Les autres modèles de Parametrage ne sont pas directement utilisés dans ce contrôleur de rapport pour les requêtes de base,
// mais sont utilisés dans les relations des modèles Immobilisation et Transfert.
// use App\Models\Parametrage\Bureau;
// use App\Models\Parametrage\Employe;
// use App\Models\Parametrage\Fournisseur;
// use App\Models\Parametrage\GroupeTypeImmo;
// use App\Models\Parametrage\SousTypeImmo;
// use App\Models\Parametrage\StatusImmo;
// use App\Models\Parametrage\Vehicule;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class ImmobilisationRapportController extends Controller
{
    /**
     * Récupère les données pour les rapports (enregistrement ou transfert) en fonction des filtres.
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
                    // 'date_fin_acquisition' est actuellement géré par 'date_debut_acquisition' dans la requête
                    // Si tu l'as ajouté comme filtre distinct, ajoute-le ici.
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
                // Si tu as un filtre date_fin_acquisition séparé, tu peux l'ajouter ici
                // if ($request->filled('date_fin_acquisition')) {
                //     $query->whereDate('date_acquisition', '<=', $request->input('date_fin_acquisition'));
                // }

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

            default:
                $success = false;
                $message = 'Type de rapport non valide.';
                $data = [];
                break;
        }

        return new PostResource($success, $message, $data);
    }

    /**
     * Génère le PDF pour les rapports (enregistrement ou transfert) en fonction des filtres.
     */
    public function imprimerRapportData(Request $request)
    {
        $typeRapport = $request->input('id_type_rapport');
        $data = null;
        $viewName = '';
        $filename = '';

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
                    // 'date_fin_acquisition' est géré implicitement par le frontend s'il est vide
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
                // Si tu as un filtre date_fin_acquisition séparé, tu peux l'ajouter ici
                // if ($request->filled('date_fin_acquisition')) {
                //     $query->whereDate('date_acquisition', '<=', $request->input('date_fin_acquisition'));
                // }

                $data = $query->latest()->get(); // Pas de pagination pour le PDF
                $viewName = 'pdf.rapport.rapport_immobilisations'; // Chemin de la vue pour l'enregistrement
                $filename = 'rapport_enregistrement_immobilisations.pdf';
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
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Type de rapport non valide pour l\'impression.'], 400);
        }

        // Le nom de la variable dans la vue dépendra du type de rapport
        $compactData = ($typeRapport === 'enregistrement') ? ['immobilisations' => $data] : ['transferts' => $data];
        $pdf = \Pdf::loadView($viewName, $compactData);

        return $pdf->download($filename);
    }
}
