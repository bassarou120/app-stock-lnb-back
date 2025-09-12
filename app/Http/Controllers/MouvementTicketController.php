<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\StockTicket;
use App\Models\MouvementTicket;
use App\Models\Trajet;
use App\Http\Resources\PostResource;
use App\Models\Parametrage\CouponTicket;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDF;


/**
 * @OA\Get(
 * path="/api/mouvement-ticket/entree",
 * tags={"Mouvement Ticket"},
 * summary="Lister les mouvements d'entrée de ticket",
 * @OA\Response(
 * response=200,
 * description="Liste des mouvements d'entrée",
 * @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource")
 * )
 * )
 *
 * @OA\Post(
 * path="/api/mouvement-ticket/entree",
 * tags={"Mouvement Ticket"},
 * summary="Créer un mouvement d'entrée de ticket",
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"compagnie_petrolier_id", "coupon_ticket_id", "qte", "date"},
 * @OA\Property(property="compagnie_petrolier_id", type="integer", example=1),
 * @OA\Property(property="coupon_ticket_id", type="integer", example=2),
 * @OA\Property(property="description", type="string", example="Tickets reçus"),
 * @OA\Property(property="objet", type="string", example="Réapprovisionnement"),
 * @OA\Property(property="qte", type="integer", example=100),
 * @OA\Property(property="date", type="string", format="date", example="2025-07-01")
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="Mouvement créé",
 * @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource")
 * )
 * )
 *
 * @OA\Put(
 * path="/api/mouvement-ticket/entree/{id}",
 * tags={"Mouvement Ticket"},
 * summary="Mettre à jour un mouvement d'entrée de ticket",
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(ref="#/components/schemas/MouvementTicket")
 * ),
 * @OA\Response(response=200, description="Mouvement mis à jour", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Delete(
 * path="/api/mouvement-ticket/entree/{id}",
 * tags={"Mouvement Ticket"},
 * summary="Supprimer un mouvement d'entrée de ticket",
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Mouvement supprimé", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Get(
 * path="/api/mouvement-ticket/sortie",
 * tags={"Mouvement Ticket"},
 * summary="Lister les mouvements de sortie de ticket",
 * @OA\Response(response=200, description="Liste des mouvements de sortie", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Post(
 * path="/api/mouvement-ticket/sortie",
 * tags={"Mouvement Ticket"},
 * summary="Créer un mouvement de sortie de ticket",
 * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/MouvementTicket")),
 * @OA\Response(response=201, description="Mouvement créé", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Put(
 * path="/api/mouvement-ticket/sortie/{id}",
 * tags={"Mouvement Ticket"},
 * summary="Mettre à jour un mouvement de sortie de ticket",
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/MouvementTicket")),
 * @OA\Response(response=200, description="Mouvement mis à jour", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Delete(
 * path="/api/mouvement-ticket/sortie/{id}",
 * tags={"Mouvement Ticket"},
 * summary="Supprimer un mouvement de sortie de ticket",
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Mouvement supprimé", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Get(
 * path="/api/quantite-disponible-ticket/{idCoupon}/{idCompagnie}",
 * tags={"Mouvement Ticket"},
 * summary="Obtenir la quantité disponible d'un ticket",
 * @OA\Parameter(name="idCoupon", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Parameter(name="idCompagnie", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Quantité retournée", @OA\JsonContent(ref="#/components/schemas/MouvementTicketResource"))
 * )
 *
 * @OA\Post(
 * path="/api/get-quantite-ticket-attribution",
 * tags={"Mouvement Ticket"},
 * summary="Calculer la quantité de ticket nécessaire pour un trajet",
 * @OA\RequestBody(required=true,
 * @OA\JsonContent(
 * required={"commune_depart", "commune_arriver", "trajet_aller_retour", "coupon_ticket_id"},
 * @OA\Property(property="commune_depart", type="integer", example=1),
 * @OA\Property(property="commune_arriver", type="integer", example=2),
 * @OA\Property(property="trajet_aller_retour", type="boolean", example=true),
 * @OA\Property(property="coupon_ticket_id", type="integer", example=3)
 * )
 * ),
 * @OA\Response(response=200, description="Quantité calculée")
 * )
 */


class MouvementTicketController extends Controller
{
    // Afficher la liste des mouvements
    public function indexEntreeTicket()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Ticket')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementTicket::with(['compagniePetrolier', 'coupon_ticket'])
                ->where('id_type_mouvement', $type_mouvement->id)
                ->where('isdeleted', false)
                ->latest()
                ->paginate(1000);

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
            "qte" => 'required|integer|min:1', // Ajout de min:1
            "date" => 'required|date', // Ajout de date
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Ticket")->latest()->first();

        if (!$type_mouvement) {
            return response()->json(['error' => "Le type de mouvement 'Entrée de Ticket' n'existe pas."], 404);
        }

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            $b = MouvementTicket::create([
                "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
                "coupon_ticket_id" => $request->coupon_ticket_id,
                "description" => $request->description,
                "id_type_mouvement" => $type_mouvement->id,
                "qte" => $request->qte,
                "objet" => $request->objet,
                "date" => $request->date,
            ]);

            $stockTicket = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)
                ->where('compagnie_petrolier_id', $request->compagnie_petrolier_id)
                ->where('isdeleted', false) // Ajout de la condition isdeleted
                ->first();

            if (!$stockTicket) {
                $stockTicket = StockTicket::create([
                    'coupon_ticket_id' => $request->coupon_ticket_id,
                    'compagnie_petrolier_id' => $request->compagnie_petrolier_id,
                    'qte_actuel' => 0,
                    'isdeleted' => false, // Assurez-vous que le flag isdeleted est défini
                ]);
            }

            $stockTicket->qte_actuel += $request->qte;
            $stockTicket->save();

            DB::commit();
            return new PostResource(true, 'Le mouvement d\'entrée de ticket a été bien enregistré !', $b);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du mouvement d\'entrée: ' . $e->getMessage()], 500);
        }
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
            "qte" => 'required|integer|min:1', // Ajout de min:1
            "date" => 'required|date', // Ajout de date
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Récupérer l'ancien stock avant modification
            $ancien_qte = $mouvement->qte;
            $ancien_coupon_id = $mouvement->coupon_ticket_id;
            $ancien_compagnie_id = $mouvement->compagnie_petrolier_id;

            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Ticket")->latest()->first();
            if (!$type_mouvement) {
                throw new \Exception("Le type de mouvement 'Entrée de Ticket' n'existe pas.");
            }

            // Mise à jour du mouvement
            $mouvement->update([
                "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
                "coupon_ticket_id" => $request->coupon_ticket_id,
                "description" => $request->description,
                "qte" => $request->qte,
                "objet" => $request->objet,
                "date" => $request->date,
                "id_type_mouvement" => $type_mouvement->id,
            ]);

            // Réajuster l'ancien stock
            $oldStock = StockTicket::where('coupon_ticket_id', $ancien_coupon_id)
                ->where('compagnie_petrolier_id', $ancien_compagnie_id)
                ->where('isdeleted', false)
                ->first();
            if ($oldStock) {
                $oldStock->qte_actuel -= $ancien_qte;
                if ($oldStock->qte_actuel < 0) $oldStock->qte_actuel = 0; // Empêcher les quantités négatives
                $oldStock->save();
            }

            // Mettre à jour le nouveau stock (ou le même si coupon/compagnie n'ont pas changé)
            $newStock = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)
                ->where('compagnie_petrolier_id', $request->compagnie_petrolier_id)
                ->where('isdeleted', false)
                ->first();

            if (!$newStock) {
                $newStock = StockTicket::create([
                    'coupon_ticket_id' => $request->coupon_ticket_id,
                    'compagnie_petrolier_id' => $request->compagnie_petrolier_id,
                    'qte_actuel' => 0,
                    'isdeleted' => false,
                ]);
            }
            $newStock->qte_actuel += $request->qte;
            $newStock->save();

            DB::commit();
            // Retourner la réponse
            return new PostResource(true, 'Le mouvement d\'entrée de ticket a été mis à jour avec succès !', $mouvement);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la mise à jour du mouvement d\'entrée: ' . $e->getMessage()], 500);
        }
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

        DB::beginTransaction();
        try {
            // Chercher le stock correspondant à la combinaison coupon + compagnie
            $stock = StockTicket::where('coupon_ticket_id', $mouvement->coupon_ticket_id)
                ->where('compagnie_petrolier_id', $mouvement->compagnie_petrolier_id)
                ->where('isdeleted', false)
                ->first();

            if ($stock) {
                // Réduire la quantité du stock
                $stock->qte_actuel -= $mouvement->qte;

                // Empêcher que la quantité devienne négative
                if ($stock->qte_actuel < 0) {
                    $stock->qte_actuel = 0;
                }
                $stock->save();
            }

            // Supprimer logiquement le mouvement
            $mouvement->isdeleted = true;
            $mouvement->save(); // Utilisez save() pour la suppression logique

            DB::commit();
            return new PostResource(true, 'Mouvement supprimé avec succès !', null);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la suppression du mouvement d\'entrée: ' . $e->getMessage()], 500);
        }
    }


    public function indexSortieTicket()
{
    // Récupérer l'ID du type de mouvement "Sortie de Ticket"
    $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

    if (!$type_mouvement) {
        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    }

    // 1. Récupérer tous les mouvements de sortie, en s'assurant de charger les relations
    $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'vehicule.modele', 'vehicule.marque', 'coupon_ticket', 'depart', 'arriver'])
        ->where('id_type_mouvement', $type_mouvement->id)
        ->where('isdeleted', false)
        ->latest() // Il est important de trier pour que le premier élément du groupe soit cohérent
        ->get();

    // 2. Grouper les mouvements par leur référence commune
    $groupedMouvements = $mouvements->groupBy('reference');

    // 3. Transformer chaque groupe en un seul objet consolidé pour le frontend
    $transactions = $groupedMouvements->map(function ($group) {
        // Prendre le premier mouvement comme base pour les informations communes
        $firstMouvement = $group->first();

        // Créer un tableau contenant les détails de chaque ticket du groupe
        $ticketsDetails = $group->map(function ($mouvement) {
            return [
                'coupon' => $mouvement->coupon_ticket,
                'compagnie' => $mouvement->compagniePetrolier,
                'qte' => $mouvement->qte,
            ];
        });

        // Retourner un objet unique par transaction
        return [
            "id" => $firstMouvement->id, // ID du premier mouvement du groupe
            'reference' => $firstMouvement->reference,
            'date' => $firstMouvement->date,
            'vehicule' => $firstMouvement->vehicule,
            'employe' => $firstMouvement->employe,
            'objet' => $firstMouvement->objet,
            'description' => $firstMouvement->description,
            'commune_depart' => $firstMouvement->depart,
            'commune_arriver' => $firstMouvement->arriver,
            'trajet_aller_retour' => $firstMouvement->trajet_aller_retour,
            'kilometrage' => $firstMouvement->kilometrage, // Assurez-vous que ces champs existent
            'kilometrage_de_fin' => $firstMouvement->kilometrage_de_fin,
            'tickets' => $ticketsDetails, // Le tableau des tickets
        ];
    })->values(); // Utiliser values() pour réindexer le tableau numériquement

    // Note : La pagination sur un résultat groupé est plus complexe.
    // Pour l'instant, nous renvoyons la collection complète.
    // Si la pagination est cruciale, des stratégies plus avancées sont nécessaires.
    return new PostResource(true, 'Liste des mouvements de sortie de Ticket', $transactions);
}



    //Sortie de ticket
    // public function indexSortieTicket()
    // {
    //     $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

    //     if ($type_mouvement) {
    //         $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'vehicule.modele', 'vehicule.marque', 'coupon_ticket', 'depart', 'arriver'])
    //             ->where('id_type_mouvement', $type_mouvement->id)
    //             ->where('isdeleted', false)
    //             ->latest()->paginate(1000);

    //         return new PostResource(true, 'Liste des mouvements de sortie de Ticket', $mouvements);
    //     }
    //     return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    // }

    // store

    public function storeSortieTicket(Request $request)
{
    // Validation globale
    $validator = Validator::make($request->all(), [
        "vehicule_id" => 'required|exists:vehicules,id',
        "employe_id" => 'required|exists:employes,id',
        "date" => 'required|date',
        "trajet_aller_retour" => 'required|boolean',
        "description" => 'nullable|string|max:255',
        "objet" => 'nullable|string|max:255',
        "commune_depart" => 'nullable|exists:communes,id',
        "commune_arriver" => 'nullable|exists:communes,id',
        "kilometrage" => 'required|integer|min:0', // 👈 AJOUTEZ CETTE LIGNE
        "kilometrage_de_fin" => 'nullable|integer|min:0', // 👈 AJOUTEZ CETTE LIGNE
        "tickets" => 'required|array|min:1',
        "tickets.*.compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
        "tickets.*.coupon_ticket_id" => 'required|exists:coupon_tickets,id',
        "tickets.*.qte" => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Type mouvement
    $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Ticket")->first();
    if (!$type_mouvement) {
        return response()->json(['error' => "Type de mouvement 'Sortie de Ticket' introuvable"], 404);
    }

    DB::beginTransaction();
    try {
        $mouvements = [];
        // Générer référence
        $reference = strtoupper(uniqid('MVT-'));

        foreach ($request->tickets as $ticket) {
            // Vérifier stock
            $stock = StockTicket::where('coupon_ticket_id', $ticket['coupon_ticket_id'])
                ->where('compagnie_petrolier_id', $ticket['compagnie_petrolier_id'])
                ->where('isdeleted', false)
                ->first();

            if (!$stock || $stock->qte_actuel < $ticket['qte']) {
                DB::rollBack();
                return response()->json([
                    'error' => "Quantité insuffisante pour coupon {$ticket['coupon_ticket_id']} de la compagnie {$ticket['compagnie_petrolier_id']}."
                ], 400);
            }



            // Créer mouvement (les champs communs sont pris du root)
            $mouvement = MouvementTicket::create([
                "id_type_mouvement" => $type_mouvement->id,
                "vehicule_id" => $request->vehicule_id,
                "compagnie_petrolier_id" => $ticket['compagnie_petrolier_id'],
                "coupon_ticket_id" => $ticket['coupon_ticket_id'],
                "employe_id" => $request->employe_id,
                "description" => $request->description ?? null,
                "qte" => $ticket['qte'],
                "objet" => $request->objet ?? null,
                "date" => $request->date,
                "commune_depart" => $request->commune_depart ?? null,
                "commune_arriver" => $request->commune_arriver ?? null,
                "kilometrage" => $request->kilometrage,
                "kilometrage_de_fin" => $request->kilometrage_de_fin ?? null,
                "trajet_aller_retour" => $request->trajet_aller_retour,
                "reference" => $reference,
            ]);

            // Déduire stock
            $stock->qte_actuel -= $ticket['qte'];
            $stock->save();

            $mouvements[] = $mouvement;
        }

        DB::commit();

        return new PostResource(true, "Sortie de tickets enregistrée avec succès !", $mouvements);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Erreur : '.$e->getMessage()], 500);
    }
}


    // update sortie
    public function updateSortieTicket(Request $request, $id)
{
    // Valider les champs qui sont communs à toute la transaction
    $validator = Validator::make($request->all(), [
        "vehicule_id" => 'required|exists:vehicules,id',
        "employe_id" => 'required|exists:employes,id',
        "description" => 'nullable|string|max:255',
        "objet" => 'nullable|string|max:255',
        "date" => 'required',
        'trajet_aller_retour' => 'required|boolean',
        'kilometrage' => 'required|integer', // Valider le kilométrage de début
        'kilometrage_de_fin' => 'nullable|integer', // Valider le kilométrage de fin
        'commune_depart' => 'required|exists:communes,id',
        'commune_arriver' => 'required|exists:communes,id',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Étape 1 : Trouver le mouvement initial pour obtenir sa référence
    $mouvementInitial = MouvementTicket::find($id);
    if (!$mouvementInitial) {
        return response()->json(['error' => 'Mouvement introuvable.'], 404);
    }

    DB::beginTransaction();
    try {
        // Étape 2 : Mettre à jour tous les mouvements qui ont la même référence
        $affectedRows = MouvementTicket::where('reference', $mouvementInitial->reference)->update([
            "vehicule_id" => $request->vehicule_id,
            "employe_id" => $request->employe_id,
            "description" => $request->description,
            "objet" => $request->objet,
            "date" => $request->date,
            "commune_depart" => $request->commune_depart,
            "commune_arriver" => $request->commune_arriver,
            "trajet_aller_retour" => $request->trajet_aller_retour,
            "kilometrage" => $request->kilometrage,
            "kilometrage_de_fin" => $request->kilometrage_de_fin,
        ]);

        DB::commit();

        // Retourner le mouvement initial ou un message de succès
        return new PostResource(true, "Les mouvements de sortie ont été mis à jour avec succès !", $mouvementInitial);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Erreur lors de la mise à jour des mouvements de sortie: ' . $e->getMessage()], 500);
    }
}

    //delete sortie
    public function deleteSortieTicket($id)
    {
        $mouvement = MouvementTicket::find($id);

        if (!$mouvement) {
            return response()->json([
                'success' => false,
                'message' => 'Mouvement introuvable.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Vérifier si un stock existe pour ce ticket
            $stock = StockTicket::where('coupon_ticket_id', $mouvement->coupon_ticket_id)
                ->where('compagnie_petrolier_id', $mouvement->compagnie_petrolier_id)
                ->where('isdeleted', false)
                ->first(); // Utiliser first()

            if ($stock) {
                // Réduire la quantité du stock
                $stock->qte_actuel += $mouvement->qte;

                // Empêcher que la quantité devienne négative
                if ($stock->qte_actuel < 0) {
                    $stock->qte_actuel = 0;
                }
                $stock->save();
            }

            // Supprimer logiquement le mouvement
            $mouvement->isdeleted = true;
            $mouvement->save(); // Utilisez save() pour la suppression logique

            DB::commit();
            return new PostResource(true, 'Mouvement supprimé avec succès !', null);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la suppression du mouvement de sortie: ' . $e->getMessage()], 500);
        }
    }

    // get qte disponible
    public function getQuantiteDisponible($idCoupon, $idCompagnie)
    {
        $stock = StockTicket::where('coupon_ticket_id', $idCoupon)
            ->where('compagnie_petrolier_id', $idCompagnie)
            ->where('isdeleted', false) // Assurez-vous de ne considérer que les stocks non supprimés
            ->first();
        $quantite = $stock ? $stock->qte_actuel : 0;

        return new PostResource(true, 'Quantité trouvée !', $quantite);
    }

    public function getQuantiteTicketAttribution(Request $request)
    {
        $validated = $request->validate([
            'commune_depart' => 'required|exists:communes,id',
            'commune_arriver' => 'required|exists:communes,id',
            'trajet_aller_retour' => 'required|boolean',
            'coupon_ticket_id' => 'required|exists:coupon_tickets,id',
        ]);

        $trajet = Trajet::where('commune_depart', $validated['commune_depart'])
            ->where('commune_arriver', $validated['commune_arriver'])
            ->where('trajet_aller_retour', $validated['trajet_aller_retour'])
            ->first();

        $coupon = CouponTicket::find($validated['coupon_ticket_id']);

        // Si le trajet n'existe pas, ou le coupon est invalide/valeur 0, on retourne 0
        if (!$trajet || !$coupon || $coupon->valeur == 0) {
            return response()->json([
                'qteTicket' => 0,
                'message' => 'Trajet ou coupon invalide'
            ], 200);
        }

        $valeurTrajet = $trajet->valeur;
        $valeurCoupon = $coupon->valeur;

        $qteTicket = (int) ceil($valeurTrajet / $valeurCoupon);

        return response()->json(['qteTicket' => $qteTicket], 200);
    }



    //pour ajouter le kilometrage de fin

    public function updateKilometrageDeFin(Request $request, $id)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            "kilometrage_de_fin" => "required|integer|min:0",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Chercher le mouvement
        $mouvement = MouvementTicket::find($id);

        if (!$mouvement) {
            return response()->json(['error' => "Mouvement introuvable."], 404);
        }

        // Mise à jour du kilométrage de fin
        $mouvement->kilometrage_de_fin = $request->kilometrage_de_fin;
        $mouvement->save();

        return response()->json([
            'success' => true,
            'message' => "Kilométrage de fin mis à jour avec succès",
            'data' => $mouvement
        ], 200);
    }

    public function genererBonDeSortie(Request $request, $reference)
{
    // Récupérer tous les mouvements de tickets liés à cette référence
    $mouvements = MouvementTicket::with([
        'vehicule',
        'employe',
        'coupon_ticket',
        'compagniePetrolier',
        'depart',
        'arriver'
    ])
    ->where('reference', $reference)
    ->get();

    if ($mouvements->isEmpty()) {
        return response()->json(['error' => 'Aucun mouvement de ticket trouvé pour cette référence.'], 404);
    }

    // Récupérer les informations communes pour le rapport
    $premierMouvement = $mouvements->first();
    $data = [
        'reference' => $premierMouvement->reference,
        'vehicule' => $premierMouvement->vehicule,
        'employe' => $premierMouvement->employe,
        'date' => $premierMouvement->date,
        'objet' => $premierMouvement->objet,
        'communeDepart' => $premierMouvement->depart,
        'communeArriver' => $premierMouvement->arriver,
        'kilometrage' => $premierMouvement->kilometrage,
        'kilometrage_de_fin' => $premierMouvement->kilometrage_de_fin,
        'trajet_aller_retour' => $premierMouvement->trajet_aller_retour,
        'mouvements' => $mouvements
    ];

    // Générer le PDF en utilisant la vue 'demande_sortie.blade.php'
    $pdf = PDF::loadView('pdf.sortie_ticket', $data);

    // Télécharger le PDF
    return $pdf->download('bon_de_sortie_'. $reference . '.pdf');
}
}
