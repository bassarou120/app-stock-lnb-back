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

        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();

        if (!$exerciceOuvert) {
            return response()->json(['error' => 'Aucun exercice ouvert trouvé.'], 422);
        }

        // Récupérer les coupon_tickets de l'exercice ouvert et non supprimés
        $couponTickets = CouponTicket::where('isdeleted', false)
            ->where('id_exercice', $exerciceOuvert->id)
            ->latest()
            ->paginate(1000);

        // Retourner la réponse formatée avec PostResource
        return new PostResource(true, 'Liste des coupon tickets', $couponTickets);
    }

    public function getCouponTicketsWithCompagnies()
    {

        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();

        if (!$exerciceOuvert) {
            return response()->json(['error' => 'Aucun exercice ouvert trouvé.'], 422);
        }

        $stocks = StockTicket::with(['couponTicket', 'compagnie'])
            ->where('isdeleted', false)
            ->where('id_exercice', $exerciceOuvert->id)
            ->orderByDesc('created_at')
            ->get();

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

        // Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json(['error' => 'Aucun exercice ouvert trouvé.'], 422);
        }

        // Créer un nouveau coupon_ticket avec les données valides
        $couponTicket = CouponTicket::create([
            'libelle' => $request->libelle,
            'valeur' => $request->valeur,
            'id_exercice' => $exerciceOuvert->id,
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

        // Vérifier si le coupon appartient à l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json(['error' => 'Aucun exercice ouvert trouvé.'], 422);
        }

        if ($couponTicket->id_exercice !== $exerciceOuvert->id) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de modifier un coupon d'un exercice fermé."
            ], 403);
        }

        // Mettre à jour le coupon_ticket avec les nouvelles données
        $couponTicket->update([
            'libelle' => $request->libelle,
            'valeur' => $request->valeur,
            'id_exercice' => $exerciceOuvert->id,
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'Coupon ticket modifié avec succès', $couponTicket);
    }

    // Supprimer un coupon_ticket
    public function destroy(CouponTicket $couponTicket)
    {
        // Vérifier l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();

        if (!$exerciceOuvert) {
            return response()->json(['error' => 'Aucun exercice ouvert trouvé.'], 422);
        }

        // Vérifier que le coupon appartient à l'exercice ouvert
        if ($couponTicket->id_exercice !== $exerciceOuvert->id) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer un coupon d'un exercice fermé."
            ], 403);
        }

        // Supprimer le coupon_ticket
        $couponTicket->isdeleted = true;
        $couponTicket->save();
        // Retourner la réponse formatée avec PostResource, indiquant que la suppression a réussi
        return new PostResource(true, 'Coupon ticket supprimé avec succès', null);
    }

}
