<?php

namespace App\Http\Controllers\Rapport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Immobilisation;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\Fournisseur;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use App\Models\Parametrage\StatusImmo;
use App\Models\Parametrage\Vehicule;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class ImmobilisationRapportController extends Controller
{
    /**
     * Récupère les immobilisations filtrées pour le rapport en fonction du type de rapport.
     */
    public function getImmobilisationsForReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string', // Le type de rapport est maintenant obligatoire
            'code_immo' => 'nullable|string',
            'employe_id' => 'nullable|exists:employes,id',
            'date_debut_acquisition' => 'nullable|date', // Rendu nullable ici car la validation obligatoire est gérée par le frontend et dépend du type de rapport
            'date_fin_acquisition' => 'nullable|date|after_or_equal:date_debut_acquisition',
            // Les autres champs de filtrage ne sont pas validés ici car ils dépendent du type de rapport
            // Ils peuvent être inclus si d'autres types de rapports sont ajoutés.
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Immobilisation::query();

        // Charger les relations communes à tous les rapports ou les plus importantes
        $query->with([
            'groupeTypeImmo', // Toujours pertinent pour l'affichage du type
            'sousTypeImmo',   // Toujours pertinent
            'statusImmo',     // Toujours pertinent
            'employe',        // Pertinent pour le rapport d'enregistrement et d'autres
            // 'bureau',       // Ces relations seront chargées si leurs filtres sont utilisés
            // 'fournisseur',
            // 'vehicule'
        ]);

        // Logique de filtrage basée sur le type de rapport
        switch ($request->id_type_rapport) {
            case 'enregistrement':
                // Pour le rapport d'enregistrement, on filtre par date d'acquisition, code, et employé.
                // Les dates d'acquisition sont obligatoires pour ce rapport, mais la validation
                // côté serveur peut être plus souple si le frontend gère déjà cela fermement.
                // Ici, nous nous assurons que les données sont présentes si elles sont envoyées.

                if ($request->filled('date_debut_acquisition')) {
                    $query->where('date_acquisition', '>=', $request->date_debut_acquisition);
                }
                if ($request->filled('date_fin_acquisition')) {
                    $query->where('date_acquisition', '<=', $request->date_fin_acquisition);
                }
                if ($request->filled('code_immo')) {
                    $query->where('code', 'like', '%' . $request->code_immo . '%');
                }
                if ($request->filled('employe_id')) {
                    $query->where('employe_id', $request->employe_id);
                }

                // Charger les relations spécifiques au rapport d'enregistrement si elles n'ont pas été chargées ci-dessus
                // Assure-toi que toutes les relations nécessaires pour les colonnes du tableau sont chargées
                $query->with([
                    'employe', // Confirmé qu'il est chargé
                    'statusImmo', // Confirmé qu'il est chargé
                    'groupeTypeImmo', // Confirmé qu'il est chargé
                    'sousTypeImmo', // Confirmé qu'il est chargé
                    // D'autres relations si elles sont affichées dans le tableau du rapport d'enregistrement :
                    // 'bureau', 'fournisseur', 'vehicule'
                ]);

                // Ajouter des filtres pour les autres champs si nécessaire, mais selon ta demande,
                // ce sont les principaux pour l'enregistrement.
                break;

            // Ajoutez d'autres cas pour d'autres types de rapports ici :
            // case 'transfert':
            //     // Appliquer les filtres spécifiques aux transferts
            //     $query->with(['ancienBureau', 'nouveauBureau', 'employeTransfert']);
            //     if ($request->filled('transfer_date')) { ... }
            //     break;
            default:
                // Pour les types de rapport non reconnus ou pour un rapport par défaut
                // Tu pourrais lancer une exception, ou retourner toutes les immobilisations
                // ou un ensemble vide. Ici, nous ne faisons rien de spécifique, la requête de base s'appliquera.
                break;
        }

        $immobilisations = $query->latest()->paginate(1000);

        return new PostResource(true, 'Rapport d\'immobilisations généré avec succès.', $immobilisations);
    }

    /**
     * Génère un PDF du rapport des immobilisations en fonction du type de rapport et des filtres.
     */
    public function imprimerRapportImmos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string',
            'code_immo' => 'nullable|string',
            'employe_id' => 'nullable|exists:employes,id',
            'date_debut_acquisition' => 'nullable|date',
            'date_fin_acquisition' => 'nullable|date|after_or_equal:date_debut_acquisition',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Immobilisation::query();

        // Charger les relations nécessaires pour le PDF
        $query->with([
            'vehicule',
            'groupeTypeImmo',
            'sousTypeImmo',
            'statusImmo',
            'employe',
            'bureau',
            'fournisseur'
        ]);

        // Logique de filtrage basée sur le type de rapport
        switch ($request->id_type_rapport) {
            case 'enregistrement':
                if ($request->filled('date_debut_acquisition')) {
                    $query->where('date_acquisition', '>=', $request->date_debut_acquisition);
                }
                if ($request->filled('date_fin_acquisition')) {
                    $query->where('date_acquisition', '<=', $request->date_fin_acquisition);
                }
                if ($request->filled('code_immo')) {
                    $query->where('code', 'like', '%' . $request->code_immo . '%');
                }
                if ($request->filled('employe_id')) {
                    $query->where('employe_id', $request->employe_id);
                }
                break;
            // Ajoutez d'autres cas ici
            default:
                // Pour les types de rapport non reconnus, tu peux choisir un comportement par défaut.
                break;
        }

        $immobilisations = $query->latest()->get(); // Pas de pagination pour le PDF

        // Le titre du PDF peut être adapté en fonction du type de rapport si tu le souhaites
        $reportTitle = 'Rapport des Immobilisations';
        if ($request->id_type_rapport === 'enregistrement') {
            $reportTitle = 'Rapport d\'Enregistrement des Immobilisations';
        }
        // else if ($request->id_type_rapport === 'transfert') { ... }


        $pdf = \Pdf::loadView('pdf.rapport.rapport_immobilisations', compact('immobilisations', 'reportTitle'));

        // Le nom du fichier PDF peut aussi dépendre du type de rapport
        $filename = 'rapport_immobilisations.pdf';
        if ($request->id_type_rapport === 'enregistrement') {
            $filename = 'rapport_enregistrement_immobilisations.pdf';
        }
        // else if ($request->id_type_rapport === 'transfert') { ... }

        return $pdf->download($filename);
    }
}
