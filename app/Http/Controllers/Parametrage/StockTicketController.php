<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;

use App\Models\Parametrage\StockTicket;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class StockTicketController extends Controller
{
    // Afficher la liste des stocks de tickets
    public function index()
    {
        $stock_tickets = StockTicket::with('couponTicket', 'compagnie')->latest()->paginate(1000);
        return new PostResource(true, 'Liste des stocks de tickets', $stock_tickets);
    }

    // Ajouter un nouveau stock de ticket
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_ticket_id' => 'required|exists:coupon_tickets,id',
            'qte_actuel' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $stock_ticket = StockTicket::create([
            'coupon_ticket_id' => $request->coupon_ticket_id,
            'qte_actuel' => $request->qte_actuel,
        ]);

        return new PostResource(true, 'Stock de ticket créé avec succès', $stock_ticket);
    }

    // Mettre à jour un stock de ticket existant
    public function update(Request $request, StockTicket $stock_ticket)
    {
        $validator = Validator::make($request->all(), [
            'coupon_ticket_id' => 'required|exists:coupon_tickets,id',
            'qte_actuel' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $stock_ticket->update([
            'coupon_ticket_id' => $request->coupon_ticket_id,
            'qte_actuel' => $request->qte_actuel,
        ]);

        return new PostResource(true, 'Stock de ticket mis à jour avec succès', $stock_ticket);
    }

    // Supprimer un stock de ticket
    public function destroy(StockTicket $stock_ticket)
    {
        $stock_ticket->delete();
        return new PostResource(true, 'Stock de ticket supprimé avec succès', null);
    }
}
