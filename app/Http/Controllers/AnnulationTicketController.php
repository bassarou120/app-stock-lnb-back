<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\AnnulationTicket;
use App\Models\MouvementTicket;
use App\Models\Parametrage\StockTicket;
use App\Models\Parametrage\TypeMouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class AnnulationTicketController extends Controller
{
    public function index(Request $request)
    {
        $annulations = AnnulationTicket::with([
            'mouvement.employe',
            'mouvement.vehicule',
            'coupon',
            'compagnie',
        ])
        ->where('isdeleted', false)
        ->latest()->paginate(1000);

        // 📝 LOG → Consultation des annulations
        LogJournalisation::create([
            'action'     => 'Consultation de la liste des annulations de ticket',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

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

            // 📝 LOG → Création d'une annulation
        LogJournalisation::create([
            'action'     => 'Création d\'une annulation de ticket ID '.$annulation->id.' (qte: '.$request->qte.')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
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

        // 📝 LOG → Suppression d'une annulation
        LogJournalisation::create([
            'action'     => 'Suppression d\'annulation de ticket ID '.$annulationTicket->id.' (qte: '.$quantite.')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        $stock = StockTicket::where('coupon_ticket_id', $couponTicketId)->latest()->first();

        if ($stock) {
            $stock->qte_actuel += $quantite;
            $stock->save();
        }

        return new PostResource(true, 'Annulation de Ticket supprimée avec succès !', null);
    }

    public function getAllSortieTicketWhereNotInAnnulation(Request $request)
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

                // 📝 LOG → Consultation des mouvements de sortie non annulés
                LogJournalisation::create([
                    'action'     => 'Consultation des mouvements de sortie de ticket non annulés',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => $request->user()->id,
                    'user_name'   => $request->user()->name,
                    'date_action'=> now(),
                ]);

            return new PostResource(true, 'Liste des mouvements de sortie de Ticket non annulés', $mouvements);
        }

        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    }

    public function getMouvementInfo($idMouvement, Request $request)
    {
        $mouvement = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])
        ->where('isdeleted', false)
        ->find($idMouvement);

        if (!$mouvement) {
            return response()->json(['message' => 'Mouvement non trouvé'], 404);
        }

        // 📝 LOG → Consultation d'un mouvement spécifique
        LogJournalisation::create([
            'action'     => 'Consultation du mouvement de ticket ID '.$idMouvement,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        return response()->json([
            'compagnie_petrolier_id' => $mouvement->compagnie_petrolier_id,
            'coupon_ticket_id' => $mouvement->coupon_ticket_id,
            'quantite' => $mouvement->qte,
        ]);
    }
}
