<?php

namespace App\Http\Controllers\Rapport\Parc; // Assurez-vous que le chemin du dossier est bien app/Http/Controllers/Rapport/Parc/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Models\InterventionVehicule;
use App\Models\Parametrage\TypeIntervention;
use App\Models\Parametrage\Marque; // Assurez-vous que ce modèle existe
use App\Models\Parametrage\Modele; // Assurez-vous que ce modèle existe
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class RapportParcController extends Controller
{
    /**
     * Récupère les données de rapport pour les véhicules ou leurs interventions.
     */

    // Compteurs par type de rapport
    private $vehiculeCounter = 0;
    private $interventionVehiculeCounter = 0;
    private $vehiculeInterventionExpiranteCounter = 0;

    public function getRapportData(Request $request)
    {
        $typeRapport = $request->input('id_type_rapport');
        $data = null;
        $message = '';
        $success = true;

        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string',
        ]);

        if ($validator->fails()) {
            return new PostResource(false, 'Type de rapport manquant.', ['errors' => $validator->errors()]);
        }

        switch ($typeRapport) {
            case 'vehicule':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'nullable|date',
                    'date_fin' => 'nullable|date|after_or_equal:date_debut',
                    'modele_id' => 'nullable|exists:modeles,id', // Assurez-vous que la table est 'modeles'
                    'marque_id' => 'nullable|exists:marques,id', // Assurez-vous que la table est 'marques'
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport véhicule.', ['errors' => $validator->errors()]);
                }

                $query = Vehicule::with(['modele', 'marque']);

                if ($request->filled('date_debut') && $request->filled('date_fin')) {
                    // CORRECTION ICI : Utilisation de 'date_mise_en_service' au lieu de 'date_acquisition'
                    $query->whereBetween('date_mise_en_service', [$request->date_debut, $request->date_fin]);
                }

                if ($request->filled('modele_id')) {
                    $query->where('modele_id', $request->modele_id);
                }

                if ($request->filled('marque_id')) {
                    $query->where('marque_id', $request->marque_id);
                }

                $data = $query->latest()->paginate(1000);

                LogJournalisation::create([
                    "action"      => "Génération du rapport : " . ucfirst($typeRapport),
                    "ip_address"  => request()->ip(),
                    "user_agent"  => request()->userAgent(),
                    "user_id"     => Auth::id(),
                    "date_action" => now()
                ]);

                $message = 'Rapport des véhicules généré avec succès.';
                break;

            case 'intervention_vehicule':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'vehicule_id' => 'nullable|exists:vehicules,id',
                    'type_intervention_id' => 'nullable|exists:type_interventions,id',
                ]);

                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport intervention véhicule.', ['errors' => $validator->errors()]);
                }

                $query = InterventionVehicule::with(['vehicule','vehicule.modele', 'typeIntervention'])
                    ->whereBetween('date_intervention', [$request->date_debut, $request->date_fin]);

                if ($request->filled('vehicule_id')) {
                    $query->where('vehicule_id', $request->vehicule_id);
                }

                if ($request->filled('type_intervention_id')) {
                    $query->where('type_intervention_id', $request->type_intervention_id);
                }

                $data = $query->latest()->paginate(1000); // Utilisons 1000 pour la cohérence, ou 100 si c'est suffisant
                $message = 'Rapport des interventions sur véhicules généré avec succès.';
                
                LogJournalisation::create([
                    "action"      => "Génération du rapport : " . ucfirst($typeRapport),
                    "ip_address"  => request()->ip(),
                    "user_agent"  => request()->userAgent(),
                    "user_id"     => Auth::id(),
                    "date_action" => now()
                ]);

                break;

                case 'vehicule_intervention_expirante':
                    // Validez les champs de la requête
                    $validator = Validator::make($request->all(), [
                        'date_debut' => 'required|date',
                        'date_fin' => 'required|date|after_or_equal:date_debut',
                        'vehicule_id' => 'nullable|exists:vehicules,id',
                        'type_intervention_id' => 'nullable|exists:type_interventions,id',
                    ]);

                    if ($validator->fails()) {
                        return new PostResource(false, 'Validation échouée pour le rapport expiration intervention.', ['errors' => $validator->errors()]);
                    }

                    // On part des interventions, pas des véhicules
                    $query = InterventionVehicule::with(['vehicule.modele', 'vehicule.marque', 'typeIntervention'])
                        ->whereBetween('date_expiration', [$request->date_debut, $request->date_fin]);

                    // Appliquez les filtres si l'utilisateur a sélectionné un véhicule
                    if ($request->filled('vehicule_id')) {
                        $query->where('vehicule_id', $request->vehicule_id);
                    }

                    // Appliquez le filtre si l'utilisateur a sélectionné un type d'intervention
                    if ($request->filled('type_intervention_id')) {
                        $query->where('type_intervention_id', $request->type_intervention_id);
                    }

                    $data = $query->latest('date_expiration')->paginate(1000);
                    $message = 'Rapport des interventions expirant dans la période sélectionnée généré avec succès.';
                    
                    LogJournalisation::create([
                        "action"      => "Génération du rapport : " . ucfirst($typeRapport),
                        "ip_address"  => request()->ip(),
                        "user_agent"  => request()->userAgent(),
                        "user_id"     => Auth::id(),
                        "date_action" => now()
                    ]);
                    
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
     * Génère un PDF du rapport du parc (véhicules ou interventions).
     */
    public function imprimerRapportParc(Request $request)
    {
        $typeRapport = $request->input('id_type_rapport');
        $validator = null;
        $data = [];
        $reportTypeLabel = '';
        $filterLabels = [];

        switch ($typeRapport) {
            case 'vehicule':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'nullable|date',
                    'date_fin' => 'nullable|date|after_or_equal:date_debut',
                    'modele_id' => 'nullable|exists:modeles,id',
                    'marque_id' => 'nullable|exists:marques,id',
                ]);

                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                $query = Vehicule::with(['modele', 'marque']);

                if ($request->filled('date_debut') && $request->filled('date_fin')) {
                    // CORRECTION ICI : Utilisation de 'date_mise_en_service' au lieu de 'date_acquisition'
                    $query->whereBetween('date_mise_en_service', [$request->date_debut, $request->date_fin]);
                }

                if ($request->filled('modele_id')) {
                    $query->where('modele_id', $request->modele_id);
                }

                if ($request->filled('marque_id')) {
                    $query->where('marque_id', $request->marque_id);
                }

                $data = $query->latest()->get();
                $reportTypeLabel = 'd\'Enregistrement des Véhicules';

                // Préparer les libellés des filtres
                $filterLabels = [
                    'date_debut' => $request->filled('date_debut') ? Carbon::parse($request->date_debut)->format('d/m/Y') : 'Toutes',
                    'date_fin' => $request->filled('date_fin') ? Carbon::parse($request->date_fin)->format('d/m/Y') : 'Toutes',
                    'modele' => 'Tous',
                    'marque' => 'Toutes',
                ];
                if ($request->filled('modele_id')) {
                    $modele = Modele::find($request->modele_id);
                    $filterLabels['modele'] = $modele ? $modele->libelle_modele : 'Non trouvé';
                }
                if ($request->filled('marque_id')) {
                    $marque = Marque::find($request->marque_id);
                    $filterLabels['marque'] = $marque ? $marque->libelle : 'Non trouvée';
                }
                LogJournalisation::create([
                    "action"      => "Impression du rapport des: " . ucfirst($typeRapport),
                    "ip_address"  => request()->ip(),
                    "user_agent"  => request()->userAgent(),
                    "user_id"     => Auth::id(),
                    "date_action" => now()
                ]);

                break;

            case 'intervention_vehicule':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'vehicule_id' => 'nullable|exists:vehicules,id',
                    'type_intervention_id' => 'nullable|exists:type_interventions,id',
                ]);

                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                $query = InterventionVehicule::with(['vehicule', 'typeIntervention'])
                    ->whereBetween('date_intervention', [$request->date_debut, $request->date_fin]);

                if ($request->filled('vehicule_id')) {
                    $query->where('vehicule_id', $request->vehicule_id);
                }

                if ($request->filled('type_intervention_id')) {
                    $query->where('type_intervention_id', $request->type_intervention_id);
                }

                $data = $query->latest()->get();
                $reportTypeLabel = 'des Interventions sur Véhicules';

                // Préparer les libellés des filtres
                $filterLabels = [
                    'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
                    'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
                    'vehicule' => 'Tous',
                    'type_intervention' => 'Tous',
                ];
                if ($request->filled('vehicule_id')) {
                    $vehicule = Vehicule::find($request->vehicule_id);
                    $filterLabels['vehicule'] = $vehicule ? ($vehicule->marque->libelle . ' ' . $vehicule->modele->libelle_modele . ' (' . $vehicule->immatriculation . ')') : 'Non trouvé';
                }
                if ($request->filled('type_intervention_id')) {
                    $typeIntervention = TypeIntervention::find($request->type_intervention_id);
                    $filterLabels['type_intervention'] = $typeIntervention ? $typeIntervention->libelle_type_intervention : 'Non trouvé';
                }

                LogJournalisation::create([
                    "action"      => "Impression du rapport des: " . ucfirst($typeRapport),
                    "ip_address"  => request()->ip(),
                    "user_agent"  => request()->userAgent(),
                    "user_id"     => Auth::id(),
                    "date_action" => now()
                ]);
                break;

                case 'vehicule_intervention_expirante':
                    $validator = Validator::make($request->all(), [
                        'date_debut' => 'required|date',
                        'date_fin' => 'required|date|after_or_equal:date_debut',
                        'vehicule_id' => 'nullable|exists:vehicules,id',
                        'type_intervention_id' => 'nullable|exists:type_interventions,id',
                    ]);

                    if ($validator->fails()) {
                        return response()->json($validator->errors(), 422);
                    }

                    $query = InterventionVehicule::with(['vehicule.modele', 'vehicule.marque', 'typeIntervention'])
                        ->whereBetween('date_expiration', [$request->date_debut, $request->date_fin]);

                    if ($request->filled('vehicule_id')) {
                        $query->where('vehicule_id', $request->vehicule_id);
                    }

                    if ($request->filled('type_intervention_id')) {
                        $query->where('type_intervention_id', $request->type_intervention_id);
                    }

                    $data = $query->latest('date_expiration')->get();
                    $reportTypeLabel = 'des Interventions arrivant à expiration';

                    $filterLabels = [
                        'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
                        'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
                        'vehicule' => 'Tous',
                        'type_intervention' => 'Tous',
                    ];
                    if ($request->filled('vehicule_id')) {
                        $vehicule = Vehicule::find($request->vehicule_id);
                        $filterLabels['vehicule'] = $vehicule ? ($vehicule->marque->libelle . ' ' . $vehicule->modele->libelle_modele . ' (' . $vehicule->immatriculation . ')') : 'Non trouvé';
                    }
                    if ($request->filled('type_intervention_id')) {
                        $typeIntervention = TypeIntervention::find($request->type_intervention_id);
                        $filterLabels['type_intervention'] = $typeIntervention ? $typeIntervention->libelle_type_intervention : 'Non trouvé';
                    }

                    LogJournalisation::create([
                        "action"      => "Impression du rapport des: " . ucfirst($typeRapport),
                        "ip_address"  => request()->ip(),
                        "user_agent"  => request()->userAgent(),
                        "user_id"     => Auth::id(),
                        "date_action" => now()
                    ]);

                break;


                    default:
                        return response()->json(['success' => false, 'message' => 'Type de rapport non valide.'], 400);
                }

        // ✅ Génération du numéro de rapport ici
        $numeroRapport = $this->generateNewRapportNumber($typeRapport);

        // Passer la variable à la vue
        $pdf = Pdf::loadView('pdf.rapport.rapport_parc', compact(
            'data',
            'reportTypeLabel',
            'filterLabels',
            'typeRapport',
            'numeroRapport' // ajouté
        ));

        $filename = 'rapport_parc_' . $typeRapport . '.pdf';

        return $pdf->download($filename);
    }

    private function generateNewRapportNumber(string $typeRapport)
    {
        $prefixes = [
            'vehicule' => 'V-ENR',
            'intervention_vehicule' => 'V-INTER',
            'vehicule_intervention_expirante' => 'V-EXP',
        ];

        if (!isset($prefixes[$typeRapport])) {
            throw new \InvalidArgumentException("Type de rapport non reconnu : $typeRapport");
        }

        $prefix = $prefixes[$typeRapport];

        // Incrémenter le compteur correspondant
        switch ($typeRapport) {
            case 'vehicule':
                $this->vehiculeCounter++;
                $newNumber = $this->vehiculeCounter;
                break;

            case 'intervention_vehicule':
                $this->interventionVehiculeCounter++;
                $newNumber = $this->interventionVehiculeCounter;
                break;

            case 'vehicule_intervention_expirante':
                $this->vehiculeInterventionExpiranteCounter++;
                $newNumber = $this->vehiculeInterventionExpiranteCounter;
                break;
        }

        // Format final => "V-ENR-0001", "V-INTER-0001", etc.
        return $prefix . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

}
