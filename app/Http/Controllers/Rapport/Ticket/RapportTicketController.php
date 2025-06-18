<?php

namespace App\Http\Controllers\Rapport\Ticket; // Assurez-vous que le chemin du dossier est bien app/Http/Controllers/Rapport/Ticket/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MouvementTicket;
use App\Models\RetourTicket;
use App\Models\AnnulationTicket;
use App\Models\Parametrage\TypeMouvement; // Pour trouver l'ID des types de mouvement de ticket
use App\Models\CouponTicket; // Pour le filtre et le libellé du coupon
use App\Models\Compagnie; // Pour le filtre et le libellé de la compagnie
use App\Models\Employe; // Pour le filtre de l'employé dans les sorties
use App\Models\Vehicule; // Pour le filtre du véhicule dans les sorties
use App\Models\Depart; // Pour le filtre du lieu de départ
use App\Models\Arriver; // Pour le filtre du lieu d'arrivée
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon; // Pour formater les dates

class RapportTicketController extends Controller
{
    /**
     * Récupère les données de rapport pour les mouvements de ticket.
     */
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
            case 'entree ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_ticket_id' => 'nullable|exists:coupon_tickets,id',
                    'compagnie_petrolier_id' => 'nullable|exists:compagnie_petroliers,id',
                ]);
                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport d\'entrée ticket.', ['errors' => $validator->errors()]);
                }

                $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Ticket')->first();
                if ($type_mouvement) {
                    $query = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])
                        ->where('id_type_mouvement', $type_mouvement->id)
                        ->whereBetween('date', [$request->date_debut, $request->date_fin]);

                    if ($request->filled('coupon_ticket_id')) {
                        $query->where('coupon_ticket_id', $request->coupon_ticket_id);
                    }
                    if ($request->filled('compagnie_petrolier_id')) {
                        $query->where('compagnie_petrolier_id', $request->compagnie_petrolier_id);
                    }

                    $data = $query->latest()->paginate(1000);
                    $message = "Liste des mouvements d'entrée de ticket.";
                } else {
                    $success = false;
                    $message = "Aucun mouvement trouvé pour 'Entrée de Ticket'.";
                    $data = [];
                }
                break;

            case 'sortie ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_ticket_id' => 'nullable|exists:coupon_tickets,id',
                    'compagnie_petrolier_id' => 'nullable|exists:compagnie_petroliers,id',
                    'employe_id' => 'nullable|exists:employes,id',
                    'vehicule_id' => 'nullable|exists:vehicules,id',
                    
                ]);
                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport de sortie ticket.', ['errors' => $validator->errors()]);
                }

                $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();
                if ($type_mouvement) {
                    $query = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'coupon_ticket'])
                        ->where('id_type_mouvement', $type_mouvement->id)
                        ->whereBetween('date', [$request->date_debut, $request->date_fin]);

                    if ($request->filled('coupon_ticket_id')) {
                        $query->where('coupon_ticket_id', $request->coupon_ticket_id);
                    }
                    if ($request->filled('compagnie_petrolier_id')) {
                        $query->where('compagnie_petrolier_id', $request->compagnie_petrolier_id);
                    }
                    if ($request->filled('employe_id')) {
                        $query->where('employe_id', $request->employe_id);
                    }
                    if ($request->filled('vehicule_id')) {
                        $query->where('vehicule_id', $request->vehicule_id);
                    }
                    // if ($request->filled('depart_id')) {
                    //     $query->where('depart_id', $request->depart_id);
                    // }
                    // if ($request->filled('arriver_id')) {
                    //     $query->where('arriver_id', $request->arriver_id);
                    // }

                    $data = $query->latest()->paginate(1000);
                    $message = "Liste des mouvements de sortie de ticket.";
                } else {
                    $success = false;
                    $message = "Aucun mouvement trouvé pour 'Sortie de Ticket'.";
                    $data = [];
                }
                break;

            case 'retour ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_id' => 'nullable|exists:coupon_tickets,id', // Utilisez 'coupon_id' si c'est le nom de la colonne
                    'compagnie_id' => 'nullable|exists:compagnies,id',
                ]);
                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport de retour ticket.', ['errors' => $validator->errors()]);
                }

                $query = RetourTicket::with([
                    'mouvement.employe',
                    'mouvement.vehicule',
                    'coupon', // 'coupon' car c'est le nom de la relation sur RetourTicket
                    'compagnie' // 'compagnie' car c'est le nom de la relation sur RetourTicket
                ])->whereBetween('created_at', [$request->date_debut, $request->date_fin]); // Assurez-vous que la colonne est bien 'date_retour'

                if ($request->filled('coupon_id')) {
                    $query->where('coupon_id', $request->coupon_id);
                }
                if ($request->filled('compagnie_id')) {
                    $query->where('compagnie_petrolier_id', $request->compagnie_id);
                }

                $data = $query->latest()->paginate(1000);
                $message = "Liste des retours de ticket.";
                break;

            case 'annulation ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_id' => 'nullable|exists:coupon_tickets,id', // Utilisez 'coupon_id' si c'est le nom de la colonne
                    'compagnie_id' => 'nullable|exists:compagnies,id',
                ]);
                if ($validator->fails()) {
                    return new PostResource(false, 'Validation échouée pour le rapport d\'annulation ticket.', ['errors' => $validator->errors()]);
                }

                $query = AnnulationTicket::with([
                    'mouvement.employe',
                    'mouvement.vehicule',
                    'coupon', // 'coupon' car c'est le nom de la relation sur AnnulationTicket
                    'compagnie' // 'compagnie' car c'est le nom de la relation sur AnnulationTicket
                ])->whereBetween('created_at', [$request->date_debut, $request->date_fin]); // Assurez-vous que la colonne est bien 'date_annulation'

                if ($request->filled('coupon_id')) {
                    $query->where('coupon_id', $request->coupon_id);
                }
                if ($request->filled('compagnie_id')) {
                    $query->where('compagnie_petrolier_id', $request->compagnie_id);
                }

                $data = $query->latest()->paginate(1000);
                $message = "Liste des annulations de ticket.";
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
     * Génère un PDF du rapport des mouvements de ticket.
     */
    public function imprimerRapportTicket(Request $request)
    {
        $typeRapport = $request->input('id_type_rapport');
        $validator = null;
        $data = [];
        $reportTypeLabel = '';
        $filterLabels = [];

        switch ($typeRapport) {
            case 'entree ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_ticket_id' => 'nullable|exists:coupon_tickets,id',
                    'compagnie_petrolier_id' => 'nullable|exists:compagnie_petroliers,id',
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Ticket')->first();
                if ($type_mouvement) {
                    $query = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])
                        ->where('id_type_mouvement', $type_mouvement->id)
                        ->whereBetween('date', [$request->date_debut, $request->date_fin]);

                    if ($request->filled('coupon_ticket_id')) {
                        $query->where('coupon_ticket_id', $request->coupon_ticket_id);
                    }
                    if ($request->filled('compagnie_petrolier_id')) {
                        $query->where('compagnie_petrolier_id', $request->compagnie_petrolier_id);
                    }

                    $data = $query->latest()->get();
                    $reportTypeLabel = 'd\'Entrée de Ticket';

                    // Préparer les libellés des filtres
                    $filterLabels = [
                        'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
                        'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
                        'coupon_ticket' => 'Tous',
                        'compagnie' => 'Toutes',
                    ];
                    if ($request->filled('coupon_ticket_id')) {
                        $coupon = CouponTicket::find($request->coupon_ticket_id);
                        $filterLabels['coupon_ticket'] = $coupon ? $coupon->libelle_coupon : 'Non trouvé';
                    }
                    if ($request->filled('compagnie_petrolier_id')) {
                        $compagnie = Compagnie::find($request->compagnie_petrolier_id);
                        $filterLabels['compagnie'] = $compagnie ? $compagnie->libelle : 'Non trouvée';
                    }
                } else {
                     return response()->json(['success' => false, 'message' => "Type de mouvement 'Entrée de Ticket' non trouvé."], 404);
                }
                break;

            case 'sortie ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_ticket_id' => 'nullable|exists:coupon_tickets,id',
                    'compagnie_id' => 'nullable|exists:compagnies,id',
                    'employe_id' => 'nullable|exists:employes,id',
                    'vehicule_id' => 'nullable|exists:vehicules,id',
                    'depart_id' => 'nullable|exists:departs,id',
                    'arriver_id' => 'nullable|exists:arrivers,id',
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();
                if ($type_mouvement) {
                    $query = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'coupon_ticket', 'depart', 'arriver'])
                        ->where('id_type_mouvement', $type_mouvement->id)
                        ->whereBetween('date', [$request->date_debut, $request->date_fin]);

                    if ($request->filled('coupon_ticket_id')) {
                        $query->where('coupon_ticket_id', $request->coupon_ticket_id);
                    }
                    if ($request->filled('compagnie_id')) {
                        $query->where('compagnie_id', $request->compagnie_id);
                    }
                    if ($request->filled('employe_id')) {
                        $query->where('employe_id', $request->employe_id);
                    }
                    if ($request->filled('vehicule_id')) {
                        $query->where('vehicule_id', $request->vehicule_id);
                    }
                    if ($request->filled('depart_id')) {
                        $query->where('depart_id', $request->depart_id);
                    }
                    if ($request->filled('arriver_id')) {
                        $query->where('arriver_id', $request->arriver_id);
                    }

                    $data = $query->latest()->get();
                    $reportTypeLabel = 'de Sortie de Ticket';

                    // Préparer les libellés des filtres
                    $filterLabels = [
                        'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
                        'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
                        'coupon_ticket' => 'Tous',
                        'compagnie' => 'Toutes',
                        'employe' => 'Tous',
                        'vehicule' => 'Tous',
                        'depart' => 'Tous',
                        'arriver' => 'Tous',
                    ];
                    if ($request->filled('coupon_ticket_id')) {
                        $coupon = CouponTicket::find($request->coupon_ticket_id);
                        $filterLabels['coupon_ticket'] = $coupon ? $coupon->libelle_coupon : 'Non trouvé';
                    }
                    if ($request->filled('compagnie_id')) {
                        $compagnie = Compagnie::find($request->compagnie_id);
                        $filterLabels['compagnie'] = $compagnie ? $compagnie->nom_compagnie : 'Non trouvée';
                    }
                    if ($request->filled('employe_id')) {
                        $employe = Employe::find($request->employe_id);
                        $filterLabels['employe'] = $employe ? ($employe->nom . ' ' . $employe->prenom) : 'Non trouvé';
                    }
                    if ($request->filled('vehicule_id')) {
                        $vehicule = Vehicule::with(['marque', 'modele'])->find($request->vehicule_id);
                        $filterLabels['vehicule'] = $vehicule ? ($vehicule->marque->libelle_marque . ' ' . $vehicule->modele->libelle_modele . ' (' . $vehicule->immatriculation . ')') : 'Non trouvé';
                    }
                    if ($request->filled('depart_id')) {
                        $depart = Depart::find($request->depart_id);
                        $filterLabels['depart'] = $depart ? $depart->libelle_depart : 'Non trouvé';
                    }
                    if ($request->filled('arriver_id')) {
                        $arriver = Arriver::find($request->arriver_id);
                        $filterLabels['arriver'] = $arriver ? $arriver->libelle_arriver : 'Non trouvé';
                    }

                } else {
                    return response()->json(['success' => false, 'message' => "Type de mouvement 'Sortie de Ticket' non trouvé."], 404);
                }
                break;

            case 'retour ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_id' => 'nullable|exists:coupon_tickets,id',
                    'compagnie_id' => 'nullable|exists:compagnies,id',
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                $query = RetourTicket::with([
                    'mouvement.employe',
                    'mouvement.vehicule',
                    'coupon',
                    'compagnie'
                ])->whereBetween('created_at', [$request->date_debut, $request->date_fin]);

                if ($request->filled('coupon_id')) {
                    $query->where('coupon_id', $request->coupon_id);
                }
                if ($request->filled('compagnie_id')) {
                    $query->where('compagnie_id', $request->compagnie_id);
                }

                $data = $query->latest()->get();
                $reportTypeLabel = 'de Retour de Ticket';

                // Préparer les libellés des filtres
                $filterLabels = [
                    'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
                    'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
                    'coupon_ticket' => 'Tous',
                    'compagnie' => 'Toutes',
                ];
                if ($request->filled('coupon_id')) {
                    $coupon = CouponTicket::find($request->coupon_id);
                    $filterLabels['coupon_ticket'] = $coupon ? $coupon->libelle_coupon : 'Non trouvé';
                }
                if ($request->filled('compagnie_id')) {
                    $compagnie = Compagnie::find($request->compagnie_id);
                    $filterLabels['compagnie'] = $compagnie ? $compagnie->nom_compagnie : 'Non trouvée';
                }
                break;

            case 'annulation ticket':
                $validator = Validator::make($request->all(), [
                    'date_debut' => 'required|date',
                    'date_fin' => 'required|date|after_or_equal:date_debut',
                    'coupon_id' => 'nullable|exists:coupon_tickets,id',
                    'compagnie_id' => 'nullable|exists:compagnies,id',
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                $query = AnnulationTicket::with([
                    'mouvement.employe',
                    'mouvement.vehicule',
                    'coupon',
                    'compagnie'
                ])->whereBetween('created_at', [$request->date_debut, $request->date_fin]);

                if ($request->filled('coupon_id')) {
                    $query->where('coupon_id', $request->coupon_id);
                }
                if ($request->filled('compagnie_id')) {
                    $query->where('compagnie_id', $request->compagnie_id);
                }

                $data = $query->latest()->get();
                $reportTypeLabel = 'd\'Annulation de Ticket';

                // Préparer les libellés des filtres
                $filterLabels = [
                    'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
                    'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
                    'coupon_ticket' => 'Tous',
                    'compagnie' => 'Toutes',
                ];
                if ($request->filled('coupon_id')) {
                    $coupon = CouponTicket::find($request->coupon_id);
                    $filterLabels['coupon_ticket'] = $coupon ? $coupon->libelle_coupon : 'Non trouvé';
                }
                if ($request->filled('compagnie_id')) {
                    $compagnie = Compagnie::find($request->compagnie_id);
                    $filterLabels['compagnie'] = $compagnie ? $compagnie->nom_compagnie : 'Non trouvée';
                }
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Type de rapport non valide.'], 400);
        }

        $pdf = Pdf::loadView('pdf.rapport.rapport_ticket', compact('data', 'reportTypeLabel', 'filterLabels', 'typeRapport'));
        $filename = 'rapport_ticket_' . $typeRapport . '.pdf';

        return $pdf->download($filename);
    }
}
