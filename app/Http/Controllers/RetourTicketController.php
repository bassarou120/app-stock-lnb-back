<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RetourTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\StockTicket;
use App\Models\MouvementTicket;
use App\Models\Parametrage\TypeMouvement;





class RetourTicketController extends Controller
{
    public function index()
    {
        $retours = RetourTicket::with([
            'mouvement.employe',
            'mouvement.vehicule',
            'coupon',
            'compagnie'
        ])
        ->latest()
        ->where('isdeleted', false)
        ->paginate(1000);

        return new PostResource(true, 'Liste des retours', $retours);
    }


    // store
    public function store(Request $request)
    {

        //define validation rules
        $validator = Validator::make($request->all(), [
            "mouvementTicket_id" => 'required|exists:mouvement_tickets,id',
            "compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "qte" => 'required|integer',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        $b = RetourTicket::create([
            "mouvementTicket_id" => $request->mouvementTicket_id,
            "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
            "coupon_ticket_id" => $request->coupon_ticket_id,
            "qte" => $request->qte,
        ]);


        $stockTicket = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)
        ->where('compagnie_petrolier_id', $request->compagnie_petrolier_id)
        ->latest()
        ->where('isdeleted', false)
        ->first();

        if ($stockTicket == null) {
            $stockTicket = StockTicket::create([
                'coupon_ticket_id' => $request->coupon_ticket_id,
                'compagnie_petrolier_id' => $request->compagnie_petrolier_id,
                'qte_actuel' => 0
            ]);
        }

        $stockTicket->qte_actuel = $stockTicket->qte_actuel + $request->qte;
        $stockTicket->save();

        //return response
        return new PostResource(true, 'le mouvement d\'entrer de ticket a été bien enrégistré !', $b);
    }

    //delete entrée
    public function destroy($id)
    {
        $retourTicket = RetourTicket::find($id);

        if (!$retourTicket) {
            return response()->json([
                'success' => false,
                'message' => 'Mouvement introuvable.'
            ], 404);
        }

        // Vérifier si un stock existe pour ce ticket
        $stock = StockTicket::where('coupon_ticket_id', $retourTicket->coupon_ticket_id)
        ->where('compagnie_petrolier_id', $retourTicket->compagnie_petrolier_id)
        ->latest()
        ->where('isdeleted', false)
        ->first();

        if ($stock) {
            // Réduire la quantité du stock
            $stock->qte_actuel -= $retourTicket->qte;

            // Empêcher que la quantité devienne négative
            if ($stock->qte_actuel < 0) {
                $stock->qte_actuel = 0;
            }

            $stock->save();
        }

        // Supprimer le retourTicket

        $retourTicket->isdeleted = true;
        $retourTicket->save();

        return new PostResource(true, 'Retour Ticket supprimé avec succès !', null);
    }


    public function getAllSortieTicketWhereNotInRetour()
    {
        // Récupérer l'ID du type de mouvement "Sortie de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

        if ($type_mouvement) {
            // Récupérer les IDs des mouvements qui ont un retour
            $mouvementsAvecRetour = RetourTicket::pluck('mouvementTicket_id')->toArray();

            // Récupérer les mouvements "Sortie de Ticket" qui ne sont pas dans la liste des mouvements avec retour
            $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'coupon_ticket'])
                ->where('id_type_mouvement', $type_mouvement->id)
                ->where('isdeleted', false)
                ->whereNotIn('id', $mouvementsAvecRetour)
                ->latest()
                ->paginate(1000);

            return new PostResource(true, 'Liste des mouvements de sortie de Ticket sans retour', $mouvements);
        }

        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    }


    public function getMouvementInfo($idMouvement)
    {
        $mouvement = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])->find($idMouvement);
        if (!$mouvement) {
            return response()->json(['message' => 'Immobilisation non trouvée'], 404);
        }

        return response()->json([
            'compagnie_petrolier_id' => $mouvement->compagnie_petrolier_id,
            'coupon_ticket_id' => $mouvement->coupon_ticket_id,
            'quantite' => $mouvement->qte,
        ]);
    }
}
