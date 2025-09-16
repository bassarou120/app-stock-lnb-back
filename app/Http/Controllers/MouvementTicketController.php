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



    //Sortie de ticket
    // Afficher la liste des mouvements de sortie des tickets
    public function indexSortieTicket()
    {
        // Récupérer l'ID du type de mouvement "Sortie de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'vehicule.modele', 'vehicule.marque', 'coupon_ticket', 'depart', 'arriver'])
            ->where('id_type_mouvement', $type_mouvement->id)
            ->where('isdeleted', false)
            ->latest()->paginate(1000);

            return new PostResource(true, 'Liste des mouvements de sortie de Ticket', $mouvements);
        }
        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    }

    // store
    public function storeSortieTicket(Request $request)
    {
        // 1. Validation des données
        $validator = Validator::make($request->all(), [
            "vehicule_id" => 'required|exists:vehicules,id',
            "compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "kilometrage" => 'required|integer|min:0',
            "employe_id" => 'required|exists:employes,id',
            "description" => 'nullable|string|max:255',
            "objet" => 'nullable|string|max:255',
            "qte" => 'required|integer|min:1', // Qte manuellement entrée par l'utilisateur
            "date" => 'required|date',
            'commune_depart' => 'required|exists:communes,id',
            'commune_arriver' => 'required|exists:communes,id',
            'trajet_aller_retour' => 'required|boolean',
            // 'valeur_trajet' n'est plus envoyé par le frontend, il sera calculé
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Récupération du type de mouvement
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Ticket")->first();

        if (!$type_mouvement) {
            return response()->json(['error' => "Le type de mouvement 'Sortie de Ticket' n'existe pas."], 404);
        }

        // Début de la transaction pour assurer l'atomicité
        DB::beginTransaction();
        try {
            // 3. Recherche ou création du trajet
            $trajet = Trajet::where('commune_depart', $request->commune_depart)
                ->where('commune_arriver', $request->commune_arriver)
                ->where('trajet_aller_retour', $request->trajet_aller_retour)
                ->first();

            // Si le trajet n'existe pas, il faut le créer et calculer sa valeur
            if (!$trajet) {
                $coupon = CouponTicket::find($request->coupon_ticket_id);
                if (!$coupon) {
                    DB::rollBack();
                    return response()->json(['error' => "Coupon Ticket introuvable."], 400);
                }

                // Calcul de la valeur du nouveau trajet selon la formule demandée : valeur_trajet = valeur_coupon / qte_entree_par_utilisateur
                if ($request->qte <= 0) { // S'assurer que la quantité est positive pour éviter la division par zéro
                    DB::rollBack();
                    return response()->json(['error' => "La quantité entrée doit être supérieure à zéro pour calculer la valeur du nouveau trajet."], 400);
                }
                $valeur_nouveau_trajet = $coupon->valeur / $request->qte;

                $trajet = Trajet::create([
                    'commune_depart' => $request->commune_depart,
                    'commune_arriver' => $request->commune_arriver,
                    'trajet_aller_retour' => $request->trajet_aller_retour,
                    'valeur' => $valeur_nouveau_trajet, // Valeur calculée
                    'observation' => $request->observation ?? null, // Si vous avez un champ observation dans le formulaire principal
                ]);
            }

            // 4. Vérifier la quantité disponible en stock
            $stock = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)
                ->where('compagnie_petrolier_id', $request->compagnie_petrolier_id)
                ->where('isdeleted', false)
                ->first();

            if (!$stock || $stock->qte_actuel < $request->qte) {
                DB::rollBack();
                return response()->json(['error' => "Quantité insuffisante en stock. Disponible: " . ($stock ? $stock->qte_actuel : 0) . ", Demandé: " . $request->qte], 400);
            }

            // 5. Générer la référence automatiquement
            $reference = strtoupper(uniqid('MVT-'));

            // 6. Créer le mouvement de ticket
            $mouvement = MouvementTicket::create([
                "id_type_mouvement" => $type_mouvement->id,
                "vehicule_id" => $request->vehicule_id,
                "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
                "coupon_ticket_id" => $request->coupon_ticket_id,
                "kilometrage" => $request->kilometrage,
                "employe_id" => $request->employe_id,
                "description" => $request->description,
                "qte" => $request->qte,
                "objet" => $request->objet,
                "date" => $request->date,
                "commune_depart" => $request->commune_depart,
                "commune_arriver" => $request->commune_arriver,
                "trajet_aller_retour" => $request->trajet_aller_retour,
                "reference" => $reference,
            ]);

            // 7. Déduire la quantité du stock
            $stock->qte_actuel -= $request->qte;
            $stock->save();

            // 8. Commettre la transaction
            DB::commit();

            // Retourner la réponse
            return new PostResource(true, 'Le mouvement de sortie de ticket a été bien enregistré !', $mouvement);

        } catch (\Exception $e) {
            // En cas d'erreur, annuler la transaction
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du mouvement de sortie: ' . $e->getMessage()], 500);
        }
    }


    // update sortie
    public function updateSortieTicket(Request $request, $id)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            "vehicule_id" => 'required|exists:vehicules,id',
            "compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "kilometrage" => 'required|integer',
            "employe_id" => 'required|exists:employes,id',
            "description" => 'nullable|string|max:255',
            "objet" => 'nullable|string|max:255',
            "qte" => 'required|integer',
            "date" => 'required',
            // Assurez-vous que ces champs sont également validés si vous les utilisez dans la mise à jour
            'commune_depart' => 'required|exists:communes,id',
            'commune_arriver' => 'required|exists:communes,id',
            'trajet_aller_retour' => 'required|boolean',
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Trouver le mouvement existant
        $mouvement = MouvementTicket::find($id);
        if (!$mouvement) {
            return response()->json(['error' => 'Mouvement introuvable.'], 404);
        }

        DB::beginTransaction();
        try {
            // Vérifier le stock actuel pour ce ticket
            // Récupérer l'ancien stock avant modification
            $ancien_qte = $mouvement->qte;
            $ancien_coupon_id = $mouvement->coupon_ticket_id;
            $ancien_compagnie_id = $mouvement->compagnie_petrolier_id;


            $stock = StockTicket::where('coupon_ticket_id', $request->coupon_ticket_id)
                ->where('isdeleted', false)
                ->where('compagnie_petrolier_id', $request->compagnie_petrolier_id)
                ->first(); // Utiliser first() au lieu de latest()->first()

            if (!$stock) {
                DB::rollBack();
                return response()->json(['error' => "Stock introuvable pour cet article."], 400);
            }

            // Calculer la différence de quantité
            $differenceQte = $request->qte - $ancien_qte; // Utiliser l'ancienne quantité du mouvement pour calculer la différence

            // Vérifier si la nouvelle quantité demandée est disponible en stock
            // Si la différence est positive, cela signifie qu'on augmente la quantité de sortie, donc on doit vérifier le stock.
            if ($differenceQte > 0 && $stock->qte_actuel < $differenceQte) {
                DB::rollBack();
                return response()->json(['error' => "Quantité insuffisante en stock pour cette modification. Disponible: " . $stock->qte_actuel . ", Supplémentaire demandé: " . $differenceQte], 400);
            }


            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Ticket")->first(); // Utiliser first()
            if (!$type_mouvement) {
                DB::rollBack();
                return response()->json(['error' => "Le type de mouvement 'Sortie de Ticket' n'existe pas."], 404);
            }

            // Mise à jour du mouvement
            $mouvement->update([
                "id_type_mouvement" => $type_mouvement->id,
                "vehicule_id" => $request->vehicule_id,
                "compagnie_petrolier_id" => $request->compagnie_petrolier_id,
                "coupon_ticket_id" => $request->coupon_ticket_id,
                "kilometrage" => $request->kilometrage,
                "employe_id" => $request->employe_id,
                "description" => $request->description,
                "qte" => $request->qte,
                "objet" => $request->objet,
                "date" => $request->date,
                "commune_depart" => $request->commune_depart,
                "commune_arriver" => $request->commune_arriver,
                "trajet_aller_retour" => $request->trajet_aller_retour,
            ]);

            // Mettre à jour le stock
            $stock->qte_actuel -= $differenceQte;
            $stock->save();

            DB::commit();
            // Retourner la réponse
            return new PostResource(true, 'Le mouvement de sortie de ticket a été mis à jour avec succès !', $mouvement);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la mise à jour du mouvement de sortie: ' . $e->getMessage()], 500);
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
}
