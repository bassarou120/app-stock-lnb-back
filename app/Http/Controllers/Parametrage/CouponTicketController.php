<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\CouponTicket;
use App\Models\Parametrage\StockTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class CouponTicketController extends Controller
{
    // Afficher la liste des coupon_tickets
    public function index()
    {
        // Récupérer tous les coupon_tickets triés par ordre décroissant
        $couponTickets = CouponTicket::latest()->where('isdeleted', false)->paginate(1000);

        // Retourner la réponse formatée avec PostResource
        return new PostResource(true, 'Liste des coupon tickets', $couponTickets);
    }

    public function getCouponTicketsWithCompagnies()
    {
        $stocks = StockTicket::with(['couponTicket', 'compagnie'])
            ->orderByDesc('created_at')
            ->where('isdeleted', false)
            ->get(); // pas besoin de pagination si tu veux tout

        return new PostResource(true, 'Liste des coupons avec compagnies', $stocks);
    }


    // Créer un nouveau coupon_ticket
    public function store(Request $request)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
            'valeur' => 'required|integer',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer un nouveau coupon_ticket avec les données valides
        $couponTicket = CouponTicket::create([
            'libelle' => $request->libelle,
            'valeur' => $request->valeur,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la création a réussi
        return new PostResource(true, 'Coupon ticket créé avec succès !', $couponTicket);
    }

    // Mettre à jour un coupon_ticket existant
    public function update(Request $request, CouponTicket $couponTicket)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
            'valeur' => 'required|integer',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mettre à jour le coupon_ticket avec les nouvelles données
        $couponTicket->update([
            'libelle' => $request->libelle,
            'valeur' => $request->valeur,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'Coupon ticket modifié avec succès', $couponTicket);
    }

    // Supprimer un coupon_ticket
    public function destroy(CouponTicket $couponTicket)
    {
        // Supprimer le coupon_ticket
        $couponTicket->isdeleted = true;
        $couponTicket->save();
        // Retourner la réponse formatée avec PostResource, indiquant que la suppression a réussi
        return new PostResource(true, 'Coupon ticket supprimé avec succès', null);
    }
}
