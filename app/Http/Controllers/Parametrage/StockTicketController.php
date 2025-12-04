<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;

use App\Models\Parametrage\StockTicket;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class StockTicketController extends Controller
{
    // Afficher la liste des stocks de tickets
    public function index(Request $request)
    {
        $stock_tickets = StockTicket::with('couponTicket', 'compagnie')
        ->latest()
        ->where('isdeleted', false)
        ->paginate(1000);
        
        LogJournalisation::create([
            "action"      => "Affichage de la liste des stocks de tickets",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
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

        LogJournalisation::create([
            "action"      => "Création du stock de ticket : " . $stock_ticket->couponTicket->libelle_coupon_ticket,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
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
        
        LogJournalisation::create([
            "action"      => "Mise à jour du stock de ticket : " . $stock_ticket->couponTicket->libelle_coupon_ticket,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Stock de ticket mis à jour avec succès', $stock_ticket);
    }

    // Supprimer un stock de ticket
    public function destroy(StockTicket $stock_ticket, Request $request)
    {
        $stock_ticket->isdeleted = true;
        $stock_ticket->save();

        LogJournalisation::create([
            "action"      => "Suppression du stock de ticket : " . $stock_ticket->couponTicket->libelle_coupon_ticket,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Stock de ticket supprimé avec succès', null);
    }

    public function imprimerEtatStockTickets(Request $request)
    {
        $stock_tickets = StockTicket::with('couponTicket', 'compagnie')
        ->latest()
        ->where('isdeleted', false)
        ->get();

        $pdf = \Pdf::loadView('pdf.etat_stock_tickets', compact('stock_tickets'));

        LogJournalisation::create([
            "action"      => "Impression de l'état des stocks de tickets",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return $pdf->download('etat_stock_tickets.pdf');
    }
}
