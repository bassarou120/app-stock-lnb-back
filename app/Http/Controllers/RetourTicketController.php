<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RetourTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\StockTicket;
use App\Models\MouvementTicket;




class RetourTicketController extends Controller
{
    public function index()
    {
        $retours = RetourTicket::with([
            'mouvement.employe',
            'mouvement.vehicule',
            'coupon',
            'compagnie'
        ])->latest()->paginate(1000);

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


        $stockTicket = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)->latest()->first();

        if ($stockTicket == null) {
            $stockTicket = StockTicket::create([
                'coupon_ticket_id' => $request->coupon_ticket_id,
                'qte_actuel' => 0
            ]);
        }

        $stockTicket->qte_actuel = $stockTicket->qte_actuel + $request->qte;
        $stockTicket->save();

        //return response
        return new PostResource(true, 'le mouvement d\'entrer de ticket a été bien enrégistré !', $b);
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
