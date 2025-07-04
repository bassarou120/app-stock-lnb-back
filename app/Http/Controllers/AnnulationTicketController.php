<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\AnnulationTicket;
use App\Models\MouvementTicket;
use App\Models\Parametrage\StockTicket;
use App\Models\Parametrage\TypeMouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnulationTicketController extends Controller
{
    public function index()
    {
        $annulations = AnnulationTicket::with([
            'mouvement.employe',
            'mouvement.vehicule',
            'coupon',
            'compagnie',
        ])
        ->where('isdeleted', false)
        ->latest()->paginate(1000);

        return new PostResource(true, 'Liste des annulations', $annulations);
    }

    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            "mouvementTicket_id" => 'required|exists:mouvement_tickets,id',
            "compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "qte" => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $annulation = AnnulationTicket::create([
            "mouvementTicket_id" => $request->mouvementTicket_id,
            "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
            "coupon_ticket_id" => $request->coupon_ticket_id,
            "qte" => $request->qte,
        ]);

        $stockTicket = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)->where('compagnie_petrolier_id', $request->compagnie_petrolier_id)->where('isdeleted', false)->latest()->first();

        if ($stockTicket == null) {
            $stockTicket = StockTicket::create([
                'coupon_ticket_id' => $request->coupon_ticket_id,
                'compagnie_petrolier_id' => $request->compagnie_petrolier_id,
                'qte_actuel' => 0
            ]);
        }

        $stockTicket->qte_actuel = $stockTicket->qte_actuel + $request->qte;
        $stockTicket->save();

        return new PostResource(true, 'L\'annulation de ticket a été enregistrée avec succès !', $annulation);
    }

    public function destroy($id)
    {
        $annulationTicket = AnnulationTicket::find($id);

        if (!$annulationTicket) {
            return response()->json([
                'success' => false,
                'message' => 'Annulation introuvable.',
            ], 404);
        }

        $quantite = $annulationTicket->qte;
        $couponTicketId = $annulationTicket->coupon_ticket_id;

        $annulationTicket->isdeleted = true;
        $annulationTicket->save();

        $stock = StockTicket::where('coupon_ticket_id', $couponTicketId)->latest()->first();

        if ($stock) {
            $stock->qte_actuel += $quantite;
            $stock->save();
        }

        return new PostResource(true, 'Annulation de Ticket supprimée avec succès !', null);
    }

    public function getAllSortieTicketWhereNotInAnnulation()
    {
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

        if ($type_mouvement) {
            $mouvementsAvecAnnulation = AnnulationTicket::pluck('mouvementTicket_id')->toArray();

            $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'coupon_ticket'])
                ->where('id_type_mouvement', $type_mouvement->id)
                ->whereNotIn('id', $mouvementsAvecAnnulation)
                ->where('isdeleted', false)
                ->latest()
                ->paginate(1000);

            return new PostResource(true, 'Liste des mouvements de sortie de Ticket non annulés', $mouvements);
        }

        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    }

    public function getMouvementInfo($idMouvement)
    {
        $mouvement = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])
        ->where('isdeleted', false)
        ->find($idMouvement);

        if (!$mouvement) {
            return response()->json(['message' => 'Mouvement non trouvé'], 404);
        }

        return response()->json([
            'compagnie_petrolier_id' => $mouvement->compagnie_petrolier_id,
            'coupon_ticket_id' => $mouvement->coupon_ticket_id,
            'quantite' => $mouvement->qte,
        ]);
    }
}
