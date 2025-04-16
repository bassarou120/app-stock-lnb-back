<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\StockTicket;
use App\Models\MouvementTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;




class MouvementTicketController extends Controller
{
    // Afficher la liste des mouvements
    public function indexEntreeTicket()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Ticket')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])->where('id_type_mouvement', $type_mouvement->id)->latest()->paginate(1000);

            return new PostResource(true, 'Liste des mouvements d\'Entrée de Ticket', $mouvements);
        }
        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Entrée de Ticket".', []);
    }


    // store
    public function storeEntreeTicket(Request $request)
    {

        //define validation rules
        $validator = Validator::make($request->all(), [
            "compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "description" => 'nullable|string|max:255',
            "objet" => 'nullable|string|max:255',
            "qte" => 'required|integer',
            "date" => 'required',
            // "vehicule_id" => 'required|exists:vehicules,id',
            // "employe_id" => 'required|exists:employes,id',
            // "kilometrage" => 'required|integer',
            // "id_type_mouvement" => 'required|exists:type_mouvements,id',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Ticket")->latest()->first();

        $b = MouvementTicket::create([
            "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
            "coupon_ticket_id" => $request->coupon_ticket_id,
            "description" => $request->description,
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => $request->qte,
            "objet" => $request->objet,
            "date" => $request->date,
            // "vehicule_id" => $request->vehicule_id,
            // "employe_id" => $request->employe_id,
            // "kilometrage" => $request->kilometrage,
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

    // update entrée
    public function updateEntreeTicket(Request $request, $id)
    {
        // Vérification de l'existence du mouvement
        $mouvement = MouvementTicket::find($id);
        if (!$mouvement) {
            return response()->json(['message' => 'Mouvement introuvable'], 404);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            "compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "description" => 'nullable|string|max:255',
            "objet" => 'nullable|string|max:255',
            "qte" => 'required|integer',
            "date" => 'required',
            // "vehicule_id" => 'required|exists:vehicules,id',
            // "employe_id" => 'required|exists:employes,id',
            // "kilometrage" => 'required|integer',
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Récupérer l'ancien stock avant modification
        $ancien_qte = $mouvement->qte;

        // Mise à jour du mouvement
        $mouvement->update([
            "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
            "coupon_ticket_id" => $request->coupon_ticket_id,
            "description" => $request->description,
            "qte" => $request->qte,
            "objet" => $request->objet,
            "date" => $request->date,
            // "employe_id" => $request->employe_id,
            // "vehicule_id" => $request->vehicule_id,
            // "kilometrage" => $request->kilometrage,
        ]);

        // Mise à jour du stock
        $stock = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)->latest()->first();

        if ($stock) {
            $stock->qte_actuel = $stock->qte_actuel - $ancien_qte + $request->qte;
            $stock->save();
        } else {
            return response()->json(['message' => 'Stock introuvable'], 404);
        }

        // Retourner la réponse
        return new PostResource(true, 'Le mouvement d\'entrée de ticket a été mis à jour avec succès !', $mouvement);
    }



    //delete entrée
    public function deleteEntreeTicket($id)
    {
        $mouvement = MouvementTicket::find($id);

        if (!$mouvement) {
            return response()->json([
                'success' => false,
                'message' => 'Mouvement introuvable.'
            ], 404);
        }

        // Vérifier si un stock existe pour ce ticket
        $stock = StockTicket::where('coupon_ticket_id', $mouvement->coupon_ticket_id)->latest()->first();

        if ($stock) {
            // Réduire la quantité du stock
            $stock->qte_actuel -= $mouvement->qte;

            // Empêcher que la quantité devienne négative
            if ($stock->qte_actuel < 0) {
                $stock->qte_actuel = 0;
            }

            $stock->save();
        }

        // Supprimer le mouvement

        $mouvement->delete();

        return new PostResource(true, 'Mouvement supprimé avec succès !', null);
    }


    // Afficher la liste des mouvements de sortie des tickets
    public function indexSortieTicket()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'coupon_ticket'])->where('id_type_mouvement', $type_mouvement->id)->latest()->paginate(1000);

            return new PostResource(true, 'Liste des mouvements d\'Entrée de Ticket', $mouvements);
        }
        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Entrée de Ticket".', []);
    }
}
