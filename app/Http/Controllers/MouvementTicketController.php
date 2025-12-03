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
use Illuminate\Support\Facades\Storage;
use App\Models\CategorieSortieTicket;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PDF;
use App\Models\Exercice;
use Illuminate\Support\Facades\Auth; // Ajout pour récupérer l'ID utilisateur
use App\Models\LogJournalisation; // Ajout du modèle de journalisation
use Illuminate\Validation\ValidationException; // Ajout pour gérer spécifiquement les erreurs de validation


class MouvementTicketController extends Controller
{
    // Afficher la liste des mouvements
    public function indexEntreeTicket(Request $request)
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

            LogJournalisation::create([
                'action'     => 'Consultation des mouvements "Entrée de Ticket"',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

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

        // Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 400);
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
                'exercice_id' => $exerciceOuvert->id
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
                    'exercice_id' => $exerciceOuvert->id
                ]);
            }

            $stockTicket->qte_actuel += $request->qte;
            $stockTicket->save();

            DB::commit();
            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création Entrée Ticket réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Mouvement d'entrée de ticket créé avec succès"
            ]);

            return new PostResource(true, 'Le mouvement d\'entrée de ticket a été bien enregistré !', $b);
        } catch (\Exception $e) {
                        // 📝 LOG → Échec de validation
            // 📝 LOG → Création échouée (exception)
            LogJournalisation::create([
                'action'     => 'Création Entrée Ticket échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Erreur: " . $e->getMessage()
            ]);
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

        // Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 400);
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
                "exercice_id" => $exerciceOuvert->id
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
                    'exercice_id' => $exerciceOuvert->id
                ]);
            }
            $newStock->qte_actuel += $request->qte;
            $newStock->save();
            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour d\'Entrée de Ticket réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Mouvement d'entrée de ticket mis à jour avec succès"
            ]);

            DB::commit();
            // Retourner la réponse
            return new PostResource(true, 'Le mouvement d\'entrée de ticket a été mis à jour avec succès !', $mouvement);
        } catch (\Exception $e) {
            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Echec de Mise à jour d\'Entrée de Ticket',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Echec lors de la mise à jour du mouvement d'entrée de ticket: " . $e->getMessage()
            ]);
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la mise à jour du mouvement d\'entrée: ' . $e->getMessage()], 500);
        }
    }


    //delete entrée
    public function deleteEntreeTicket($id, Request $request)
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
            LogJournalisation::create([
                'action'     => 'Suppression logique du mouvement "Entrée de Ticket"',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

            DB::commit();
            return new PostResource(true, 'Mouvement supprimé avec succès !', null);
        } catch (\Exception $e) {
            LogJournalisation::create([
                'action'     => 'Erreur lors de la suppression du mouvement "Entrée de Ticket" (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la suppression du mouvement d\'entrée: ' . $e->getMessage()], 500);
        }
    }



    //Sortie de ticket
    // Afficher la liste des mouvements de sortie des tickets
    public function indexSortieTicket(Request $request)
    {
        // Récupérer l'ID du type de mouvement "Sortie de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        if (!$type_mouvement) {
            return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
        }

        // 1. Récupérer tous les mouvements de sortie, en s'assurant de charger les relations
        $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'vehicule.modele', 'vehicule.marque', 'coupon_ticket', 'depart', 'arriver', 'categorieSortieTicket'])
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

            LogJournalisation::create([
                'action'     => 'Consultation des mouvements "Sortie de Ticket"',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

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
                'bon_de_sortie_path' => $firstMouvement->bon_de_sortie_path,
                'tickets' => $ticketsDetails, // Le tableau des tickets
                'categorie_sortie_ticket' => $firstMouvement->categorieSortieTicket,
            ];
        })->values(); // Utiliser values() pour réindexer le tableau numériquement
        // Retourner un objet unique par transaction
        return new PostResource(true, 'Liste des mouvements de sortie de Ticket', $transactions);
    }

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
            "kilometrage" => 'nullable|integer|min:0', // 👈 AJOUTEZ CETTE LIGNE
            "kilometrage_de_fin" => 'nullable|integer|min:0', // 👈 AJOUTEZ CETTE LIGNE
            "tickets" => 'required|array|min:1',
            "tickets.*.compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            "tickets.*.coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            "tickets.*.qte" => 'required|integer|min:1',
            "id_categorie_sortie_ticket" => 'required|exists:categorie_sortie_tickets,id',
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
                    "kilometrage" => $request->kilometrage ?? null,
                    "kilometrage_de_fin" => $request->kilometrage_de_fin ?? null,
                    "trajet_aller_retour" => $request->trajet_aller_retour,
                    "reference" => $reference,
                    "id_categorie_sortie_ticket" => $request->id_categorie_sortie_ticket,
                    'exercice_id' => Exercice::where('statut', 'ouvert')->latest()->first()->id
                ]);

                // Déduire stock
                $stock->qte_actuel -= $ticket['qte'];
                $stock->save();

                $mouvements[] = $mouvement;
            }

            LogJournalisation::create([
                'action'     => 'Sortie de tickets créé avec succès',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
            DB::commit();

            return new PostResource(true, "Sortie de tickets enregistrée avec succès !", $mouvements);

        } catch (\Exception $e) {
            LogJournalisation::create([
                'action'     => 'Erreur lors de la création de la sortie de tickets',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
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
            "id_categorie_sortie_ticket" => 'required|exists:categorie_sortie_tickets,id',
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
                "id_categorie_sortie_ticket" => $request->id_categorie_sortie_ticket,
                'exercice_id' => Exercice::where('statut', 'ouvert')->latest()->first()->id
            ]);

            DB::commit();
            LogJournalisation::create([
                'action'     => 'Mise à jour des mouvements de sortie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

            // Retourner le mouvement initial ou un message de succès
            return new PostResource(true, "Les mouvements de sortie ont été mis à jour avec succès !", $mouvementInitial);

        } catch (\Exception $e) {
            LogJournalisation::create([
                'action'     => 'Erreur lors de la mise à jour des mouvements de sortie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la mise à jour des mouvements de sortie: ' . $e->getMessage()], 500);
        }
    }


    //delete sortie
    public function deleteSortieTicket($id, Request $request)
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
            LogJournalisation::create([
                'action'     => 'Suppression logique du mouvement de sortie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
            return new PostResource(true, 'Mouvement supprimé avec succès !', null);
        } catch (\Exception $e) {
            DB::rollBack();
            LogJournalisation::create([
                'action'     => 'Erreur lors de la suppression du mouvement de sortie (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
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
        $mouvement = MouvementTicket::where('isdeleted', false)->find($id);

        if (!$mouvement) {
            // 📝 LOG → Échec mise à jour (non trouvé)
            LogJournalisation::create([
                'action'     => 'Échec mise à jour KM final (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Mouvement ID: {$id} non trouvé."
            ]);
            return response()->json(['message' => 'Mouvement de sortie non trouvé.'], 404);
        }

        $reference = $mouvement->reference;
        $oldData = $mouvement->kilometrage_final;

        try {
            $validatedData = $request->validate([
                'kilometrage_final' => 'required|integer|min:' . $mouvement->kilometrage_initial,
            ]);

            DB::beginTransaction();

            MouvementTicket::where('reference', $reference)
                ->update(['kilometrage_final' => $validatedData['kilometrage_final']]);

            // Mettre à jour le statut du véhicule (remettre à "Disponible" ou un autre statut post-mission)
            if ($mouvement->id_vehicule) {
                $statusDisponible = StatusImmo::where('libelle_status_immo', 'Disponible')->first();
                if ($statusDisponible) {
                    Vehicule::where('id', $mouvement->id_vehicule)
                        ->update(['id_status_immo' => $statusDisponible->id]);
                }
            }

            DB::commit();

            // 📝 LOG → Mise à jour réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour KM final réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Référence: {$reference}. Ancien KM: {$oldData}. Nouveau KM: {$validatedData['kilometrage_final']}."
            ]);

            return new PostResource(true, 'Kilométrage de fin mis à jour avec succès.', null);

        } catch (ValidationException $e) {
            // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (mise à jour KM final)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id}. Erreurs: " . json_encode($e->errors())
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            // 📝 LOG → Mise à jour échouée (exception)
            LogJournalisation::create([
                'action'     => 'Mise à jour KM final échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id}. Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de la mise à jour du kilométrage: ' . $e->getMessage()], 500);
        }
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
            'categorieSortieTicket',
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
            'categorieSortieTicket' => $premierMouvement->categorieSortieTicket,
            'mouvements' => $mouvements
        ];

        // Générer le PDF en utilisant la vue 'demande_sortie.blade.php'
        $pdf = PDF::loadView('pdf.sortie_ticket', $data);
            LogJournalisation::create([
                'action'     => 'Génération de bon de sortie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

        // Télécharger le PDF
        return $pdf->download('bon_de_sortie_'. $reference . '.pdf');
    }

    // Fichier : app/Http/Controllers/MouvementTicketController.php
    public function televerserBonDeSortie($id, Request $request)
    {
        $mouvement = MouvementTicket::where('isdeleted', false)->find($id);

        if (!$mouvement) {
            // 📝 LOG → Échec upload (non trouvé)
            LogJournalisation::create([
                'action'     => 'Échec upload Bon de Sortie (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Mouvement ID: {$id} non trouvé."
            ]);
            return response()->json(['message' => 'Mouvement de sortie non trouvé.'], 404);
        }

        $reference = $mouvement->reference;

        try {
            $request->validate(['bon_de_sortie_file' => 'required|file|mimes:pdf|max:5120']); // 5MB max

            $file = $request->file('bon_de_sortie_file');
            $path = $file->storeAs('bons_de_sortie', $reference . '_' . time() . '.' . $file->extension(), 'public');

            DB::beginTransaction();

            // Mise à jour de tous les mouvements avec la référence
            MouvementTicket::where('reference', $reference)
                ->update(['bon_de_sortie' => $path]);

            DB::commit();

            // 📝 LOG → Upload réussi
            LogJournalisation::create([
                'action'     => 'Téléversement Bon de Sortie réussi',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Référence: {$reference}. Chemin: {$path}"
            ]);

            return new PostResource(true, 'Bon de sortie téléversé avec succès.', ['path' => $path]);

        } catch (ValidationException $e) {
             // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (upload Bon de Sortie)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id}. Erreurs: " . json_encode($e->errors())
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            // 📝 LOG → Échec upload (exception)
            LogJournalisation::create([
                'action'     => 'Téléversement Bon de Sortie échoué (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Référence: {$reference}. Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors du téléversement du bon de sortie: ' . $e->getMessage()], 500);
        }
    }

    public function voirBonDeSortie($id, Request $request)
    {
        $mouvement = MouvementTicket::find($id);

        if (!$mouvement || !$mouvement->bon_de_sortie_path) {
            return response()->json(['message' => 'Bon de sortie non trouvé.'], 404);
        }
            LogJournalisation::create([
                'action'     => 'Voir Bon de Sortie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
        return Storage::response($mouvement->bon_de_sortie_path);
    }

    public function rapportperiodique(Request $request)
    {
        Log::info('Début du rapport périodique.');

        $annee = $request->input('annee');
        $exercice = Exercice::where('id', $annee)->first();
        $annee = $exercice->annee;
        $periode = $request->input('periode', 'mensuel'); // 'mensuel' par défaut

        Log::info('Paramètres de requête', ['annee' => $annee, 'periode' => $periode]);

        if (empty($annee) || !is_numeric($annee)) {
            Log::error('Erreur: Année invalide fournie.', ['annee' => $annee]);
            return response()->json(['error' => 'Veuillez fournir une année valide.'], 400);
        }

        $rapport = [];
        $totalEntreesAcc = 0;
        $previousStockFinal = 0;

        // Déterminer les plages de mois en fonction de la période choisie
        $plages = [];
        if ($periode === 'trimestriel') {
            $plages = [
                1 => 'Trimestre 1 (Janv - Mars)',
                2 => 'Trimestre 2 (Avril - Juin)',
                3 => 'Trimestre 3 (Juil - Sept)',
                4 => 'Trimestre 4 (Oct - Déc)',
            ];
        } elseif ($periode === 'semestriel') {
            $plages = [
                1 => 'Semestre 1 (Janv - Juin)',
                2 => 'Semestre 2 (Juil - Déc)',
            ];
        } else { // 'mensuel' par défaut
            $moisLibelles = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
            ];
            foreach (range(1, 12) as $mois) {
                $plages[$mois] = $moisLibelles[$mois];
            }
        }

        Log::info('Plages de périodes déterminées.', ['plages' => $plages]);

        // Calcul du stock initial de début d'année
        $dateDebutAnnee = Carbon::create($annee, 1, 1)->startOfYear();

        $entreesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum('m.qte');

        $sortiesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum('m.qte');

        $retoursAvantAnnee = DB::table('retour_tickets')
            ->where('created_at', '<', $dateDebutAnnee)
            ->sum('qte');

        $stockInitialDebutAnnee = $entreesAvantAnnee - $sortiesAvantAnnee + $retoursAvantAnnee;
        $stockInitial = $stockInitialDebutAnnee;

        Log::info('Stock initial avant l\'année ' . $annee . ' : ' . $stockInitial);

        foreach ($plages as $index => $label) {
            $moisDebut = 0;
            $moisFin = 0;

            if ($periode === 'trimestriel') {
                $moisDebut = ($index - 1) * 3 + 1;
                $moisFin = $moisDebut + 2;
            } elseif ($periode === 'semestriel') {
                $moisDebut = ($index - 1) * 6 + 1;
                $moisFin = $moisDebut + 5;
            } else { // 'mensuel'
                $moisDebut = $index;
                $moisFin = $index;
            }

            $dateDebut = Carbon::create($annee, $moisDebut, 1)->startOfMonth();
            $dateFin   = Carbon::create($annee, $moisFin, 1)->endOfMonth();

            Log::info('Traitement de la période: ' . $label, ['dates' => [$dateDebut, $dateFin]]);

            if ($index > 1) {
                $stockInitial = $previousStockFinal;
            } else {
                $stockInitial = $stockInitialDebutAnnee;
            }

            Log::info('Stock initial pour cette période : ' . $stockInitial);

            // Calculer les entrées de la période
            $entrees = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum('m.qte');

            // Calculer les sorties de la période
            $sorties = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum('m.qte');

            // Calculer les sorties par catégorie
            $categories = DB::table('categorie_sortie_tickets')->pluck('libelle', 'id');
            $sortiesParCategorie = [];
            foreach ($categories as $id => $libelle) {
                $sortiesParCategorie[$libelle] = DB::table('mouvement_tickets as m')
                    ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                    ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                    ->where('m.id_categorie_sortie_ticket', $id)
                    ->whereBetween('m.date', [$dateDebut, $dateFin])
                    ->sum('m.qte');
            }

            // Calculer les retours de la période
            $retours = DB::table('retour_tickets')
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->sum('qte');

            // Calculer le stock final de la période
            $stockFinal = $stockInitial + $entrees - $sorties + $retours;
            $totalEntreesAcc += $entrees;

            Log::info('Calculs pour la période ' . $label, [
                'entrees' => $entrees,
                'sorties' => $sorties,
                'retours' => $retours,
                'stock_final' => $stockFinal
            ]);

            $previousStockFinal = $stockFinal;

            $rapport[] = [
                'periode' => $label,
                'stock_initial' => $stockInitial,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'sorties_par_categorie' => $sortiesParCategorie,
                'retours' => $retours,
                'stock_final' => $stockFinal,
                'total_entrees_cumulees' => $totalEntreesAcc,
            ];
        }

        Log::info('Fin du rapport périodique.');
        // Vous pouvez utiliser dd() pour voir le rapport final
        // dd($rapport);
        //return response()->json($rapport);
            LogJournalisation::create([
                'action'     => 'Rapport périodique généré',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

        return new PostResource(true, 'Rapport généré avec succès', $rapport);
    }

    public function rapportperiodiqueMontant(Request $request)
    {
        Log::info('Début du rapport périodique (montants).');

        $anneeId = $request->input('annee');
        $exercice = Exercice::where('id', $anneeId)->first();

        if (!$exercice) {
            Log::error('Erreur: Exercice invalide fourni.', ['id' => $anneeId]);
            return response()->json(['error' => 'Veuillez fournir un exercice valide.'], 400);
        }

        $annee = $exercice->annee;
        $periode = $request->input('periode', 'mensuel'); // 'mensuel' par défaut

        Log::info('Paramètres de requête', ['annee' => $annee, 'periode' => $periode]);

        $rapport = [];
        $totalEntreesAcc = 0;
        $previousStockFinal = 0;

        // 💡 INITIALISATION POUR LE CUMUL DES DÉTAILS GLOBAUX (pour le Tableau 2)
        $globalDetails = [
            'entrees' => [],
            'sorties' => [],
            'retours' => [],
        ];

        // Déterminer les plages
        $plages = [];
        if ($periode === 'trimestriel') {
            $plages = [
                1 => 'Trimestre 1 (Janv - Mars)',
                2 => 'Trimestre 2 (Avril - Juin)',
                3 => 'Trimestre 3 (Juil - Sept)',
                4 => 'Trimestre 4 (Oct - Déc)',
            ];
        } elseif ($periode === 'semestriel') {
            $plages = [
                1 => 'Semestre 1 (Janv - Juin)',
                2 => 'Semestre 2 (Juil - Déc)',
            ];
        } else {
            $moisLibelles = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
            ];
            foreach (range(1, 12) as $mois) {
                $plages[$mois] = $moisLibelles[$mois];
            }
        }

        Log::info('Plages de périodes déterminées.', ['plages' => $plages]);

        // Stock initial (en montant)
        $dateDebutAnnee = Carbon::create($annee, 1, 1)->startOfYear();

        $entreesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
            ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum(DB::raw('m.qte * c.valeur'));

        $sortiesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
            ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum(DB::raw('m.qte * c.valeur'));

        $retoursAvantAnnee = DB::table('retour_tickets as r')
            ->join('coupon_tickets as c', 'r.coupon_ticket_id', '=', 'c.id')
            ->where('r.created_at', '<', $dateDebutAnnee)
            ->sum(DB::raw('r.qte * c.valeur'));

        $stockInitialDebutAnnee = $entreesAvantAnnee - $sortiesAvantAnnee + $retoursAvantAnnee;
        $stockInitial = $stockInitialDebutAnnee;

        Log::info('Stock initial (montants) avant l\'année ' . $annee . ' : ' . $stockInitial);

        foreach ($plages as $index => $label) {
            if ($periode === 'trimestriel') {
                $moisDebut = ($index - 1) * 3 + 1;
                $moisFin = $moisDebut + 2;
            } elseif ($periode === 'semestriel') {
                $moisDebut = ($index - 1) * 6 + 1;
                $moisFin = $moisDebut + 5;
            } else {
                $moisDebut = $index;
                $moisFin = $index;
            }

            $dateDebut = Carbon::create($annee, $moisDebut, 1)->startOfMonth();
            $dateFin   = Carbon::create($annee, $moisFin, 1)->endOfMonth();

            Log::info('Traitement de la période: ' . $label, ['dates' => [$dateDebut, $dateFin]]);

            $stockInitial = ($index > 1) ? $previousStockFinal : $stockInitialDebutAnnee;

            // Entrées
            $entrees = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
                ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum(DB::raw('m.qte * c.valeur'));

            // Sorties
            $sorties = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
                ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum(DB::raw('m.qte * c.valeur'));

            // Sorties par catégorie
            $categories = DB::table('categorie_sortie_tickets')->pluck('libelle', 'id');
            $sortiesParCategorie = [];
            foreach ($categories as $id => $libelle) {
                $sortiesParCategorie[$libelle] = DB::table('mouvement_tickets as m')
                    ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                    ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
                    ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                    ->where('m.id_categorie_sortie_ticket', $id)
                    ->whereBetween('m.date', [$dateDebut, $dateFin])
                    ->sum(DB::raw('m.qte * c.valeur'));
            }

            // Retours
            $retours = DB::table('retour_tickets as r')
                ->join('coupon_tickets as c', 'r.coupon_ticket_id', '=', 'c.id')
                ->whereBetween('r.created_at', [$dateDebut, $dateFin])
                ->sum(DB::raw('r.qte * c.valeur'));

            // Stock final
            $stockFinal = $stockInitial + $entrees - $sorties + $retours;
            $totalEntreesAcc += $entrees;

            // DÉTAILS DES MOUVEMENTS PAR COUPON (pour la période actuelle)
            $detailsCoupons = [
                'entrees' => $this->getCouponDetails($dateDebut, $dateFin, 'Entrée de Ticket'),
                'sorties' => $this->getCouponDetails($dateDebut, $dateFin, 'Sortie de Ticket'),
                'retours' => $this->getCouponDetailsRetours($dateDebut, $dateFin),
            ];

            // 💡 CUMULER LES DÉTAILS DE LA SOUS-PÉRIODE VERS LES TOTAUX GLOBAUX
            $this->mergeCouponDetails($globalDetails['entrees'], $detailsCoupons['entrees']);
            $this->mergeCouponDetails($globalDetails['sorties'], $detailsCoupons['sorties']);
            $this->mergeCouponDetails($globalDetails['retours'], $detailsCoupons['retours']);

            Log::info('Calculs (montants) pour la période ' . $label, [
                'entrees' => $entrees,
                'sorties' => $sorties,
                'retours' => $retours,
                'stock_final' => $stockFinal
            ]);

            $previousStockFinal = $stockFinal;

            $rapport[] = [
                'periode' => $label,
                'stock_initial' => $stockInitial,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'sorties_par_categorie' => $sortiesParCategorie,
                'retours' => $retours,
                'stock_final' => $stockFinal,
                'total_entrees_cumulees' => $totalEntreesAcc,
                'details_coupons' => $detailsCoupons,
            ];
        }

        // 💡 FINALISATION DES DÉTAILS POUR LE TABLEAU 2 (Consolidation globale)
        $finalDetailsForTable2 = $this->aggregateFinalDetails($globalDetails);

        Log::info('Fin du rapport périodique (montants).');
            LogJournalisation::create([
                'action'     => 'Génération du rapport périodique',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

        // RETOURNER LES DEUX JEUX DE DONNÉES (Tableau 1 et Tableau 2)
        return new PostResource(true, 'Rapport généré avec succès', [
            'rapport_periodique' => $rapport,
            'details_coupons_global' => $finalDetailsForTable2,
        ]);
    }


    public function imprimerRapportPeriodique(Request $request)
    {
        Log::info("Début de la génération du PDF du rapport périodique.");

        // Récupérer l'année et la période depuis la requête
        $anneeId = $request->input('annee');
        $exercice = Exercice::where('id', $anneeId)->first();

        if (!$exercice) {
            Log::error('Erreur: Année invalide fournie.', ['anneeId' => $anneeId]);
            return response()->json(['error' => 'Veuillez fournir une année valide.'], 400);
        }

        $annee = $exercice->annee;
        $periode = $request->input('periode', 'mensuel'); // valeur par défaut

        // Réutiliser la fonction rapportperiodique pour calculer le rapport
        $rapportResource = $this->rapportperiodique(new Request([
            'annee' => $anneeId,
            'periode' => $periode
        ]));

        // Extraire les données du rapport
        $rapport = $rapportResource->response()->getData(true)['data'];

        $titre = "Rapport Périodique " . ucfirst($periode) . " - Année " . $annee;

        // Générer le PDF à partir d'une vue Blade
        $pdf = PDF::loadView('pdf.rapport-periodique', compact('rapport', 'titre'));

        Log::info("PDF généré, envoi de la réponse.");
            LogJournalisation::create([
                'action'     => 'Génération du rapport périodique',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

        return $pdf->download('rapport-periodique-' . $annee . '-' . $periode . '.pdf');
    }

    public function imprimerRapportPeriodiqueMontant(Request $request)
    {
        Log::info("Début de la génération du PDF du rapport périodique (Montant).");

        // --- 1. Validation de l'exercice et récupération des paramètres ---
        $anneeId = $request->input('annee');
        // NOTE: Assurez-vous que la classe Exercice est bien importée
        $exercice = Exercice::where('id', $anneeId)->first();

        if (!$exercice) {
            Log::error('Erreur: Année invalide fournie.', ['anneeId' => $anneeId]);
            return response()->json(['error' => 'Veuillez fournir une année valide.'], 400);
        }

        $annee = $exercice->annee;
        $periode = $request->input('periode', 'mensuel');

        // --- 2. Réutilisation de la logique de calcul (rapportperiodiqueMontant) ---

        // Simuler une requête pour la méthode de calcul du rapport
        $calculRequest = new Request([
            'annee' => $anneeId,
            'periode' => $periode
        ]);

        // Exécuter la méthode de calcul du rapport.
        // Assurez-vous que cette méthode est appelée sur l'instance courante du contrôleur ($this)
        $rapportResource = $this->rapportperiodiqueMontant($calculRequest);

        // --- 3. Extraction des données des deux tableaux ---

        // Récupérer le contenu JSON de la réponse, puis extraire la clé 'data'
        $responseData = $rapportResource->response()->getData(true)['data'];

        // Extraction des deux tableaux
        $rapport_periodique = $responseData['rapport_periodique'] ?? [];
        $details_coupons_global = $responseData['details_coupons_global'] ?? [];

        // Calcul des totaux globaux (pour le pied de page du 2e tableau dans le PDF)
        $totalCoupons = array_sum(array_column($details_coupons_global, 'nombre_coupons'));
        $totalMontant = array_sum(array_column($details_coupons_global, 'montant_total'));

        // --- 4. Préparation du titre et génération du PDF ---

        $titre = "Rapport Périodique " . ucfirst($periode) . " (Montant) - Année " . $annee;

        // Générer le PDF à partir d'une vue Blade.
        // Nous passons maintenant les deux ensembles de données et les totaux.
        $pdf = PDF::loadView('pdf.rapport-periodique-montant', compact(
            'rapport_periodique',
            'details_coupons_global',
            'titre',
            'annee',
            'periode',
            'totalCoupons',
            'totalMontant'
        ));

        // Facultatif : Définir la taille/orientation si nécessaire (ex: Paysage)
        // $pdf->setPaper('a4', 'landscape');

        Log::info("PDF généré, envoi de la réponse.");
            LogJournalisation::create([
                'action'     => 'Génération du rapport périodique par montant',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

        return $pdf->download('rapport-periodique-montant-' . $annee . '-' . $periode . '.pdf');
    }


    //25 11 2025
    private function getCouponDetails($dateDebut, $dateFin, $typeMouvement)
    {
        return DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')

            // Correction de la JOINTURE: Utiliser m.compagnie_petrolier_id
            ->join('compagnie_petroliers as co', 'm.compagnie_petrolier_id', '=', 'co.id')

            ->select(
                // CORRECTION de la colonne: Utiliser 'co.libelle' au lieu de 'co.nom_compagnie'
                'co.libelle as nom_compagnie',
                'c.valeur',
                DB::raw('SUM(m.qte) as nombre_coupons'),
                DB::raw('SUM(m.qte * c.valeur) as montant_total')
            )
            ->where('t.libelle_type_mouvement', $typeMouvement)
            ->whereBetween('m.date', [$dateDebut, $dateFin])
            ->groupBy('co.libelle', 'c.valeur') // CORRECTION: Grouper par 'co.libelle'
            ->orderBy('co.libelle')            // CORRECTION: Trier par 'co.libelle'
            ->get()
            ->toArray();
    }

    private function getCouponDetailsRetours($dateDebut, $dateFin)
    {
        return DB::table('retour_tickets as r')
            ->join('coupon_tickets as c', 'r.coupon_ticket_id', '=', 'c.id')

            // Correction de la JOINTURE: Utiliser r.compagnie_petrolier_id
            ->join('compagnie_petroliers as co', 'r.compagnie_petrolier_id', '=', 'co.id')

            ->select(
                // CORRECTION de la colonne: Utiliser 'co.libelle' au lieu de 'co.nom_compagnie'
                'co.libelle as nom_compagnie',
                'c.valeur',
                DB::raw('SUM(r.qte) as nombre_coupons'),
                DB::raw('SUM(r.qte * c.valeur) as montant_total')
            )
            ->whereBetween('r.created_at', [$dateDebut, $dateFin])
            ->groupBy('co.libelle', 'c.valeur') // CORRECTION: Grouper par 'co.libelle'
            ->orderBy('co.libelle')            // CORRECTION: Trier par 'co.libelle'
            ->get()
            ->toArray();
    }

    private function mergeCouponDetails(array &$globalArray, array $newDetails)
    {
        foreach ($newDetails as $item) {
            // CORRECTION: Convertir l'objet stdClass en tableau associatif PHP
            // Si $item est déjà un tableau (array), cette conversion n'aura pas d'effet.
            // Si $item est un objet stdClass (résultat de DB::table()->get()), il sera converti.
            $item = (array) $item;

            // Clé unique basée sur la Compagnie et la Valeur du coupon
            $key = $item['nom_compagnie'] . '|' . $item['valeur'];

            if (!isset($globalArray[$key])) {
                $globalArray[$key] = [
                    'nom_compagnie' => $item['nom_compagnie'],
                    'valeur' => $item['valeur'],
                    'nombre_coupons' => 0,
                    'montant_total' => 0.0,
                ];
            }

            $globalArray[$key]['nombre_coupons'] += $item['nombre_coupons'];
            $globalArray[$key]['montant_total'] += $item['montant_total'];
        }
    }

    private function aggregateFinalDetails(array $globalDetails): array
    {
        $combined = [];

        // Cumul des entrées, sorties et retours dans un seul tableau
        // pour obtenir la liste de TOUS les coupons impliqués.
        $this->mergeCouponDetails($combined, array_values($globalDetails['entrees']));
        $this->mergeCouponDetails($combined, array_values($globalDetails['sorties']));
        $this->mergeCouponDetails($combined, array_values($globalDetails['retours']));

        // Convertir en liste simple (tableau indexé) et trier par compagnie
        $finalList = array_values($combined);
        usort($finalList, function($a, $b) {
            return strcmp($a['nom_compagnie'], $b['nom_compagnie']);
        });

        return $finalList;
    }


    //fin 25 11 2025


    private function determinerPlages($periode)
    {
        $plages = [];
        if ($periode === 'trimestriel') {
            $plages = [
                1 => 'Trimestre 1 (Janv - Mars)',
                2 => 'Trimestre 2 (Avril - Juin)',
                3 => 'Trimestre 3 (Juil - Sept)',
                4 => 'Trimestre 4 (Oct - Déc)',
            ];
        } elseif ($periode === 'semestriel') {
            $plages = [
                1 => 'Semestre 1 (Janv - Juin)',
                2 => 'Semestre 2 (Juil - Déc)',
            ];
        } else { // 'mensuel' par défaut
            $moisLibelles = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
            ];
            foreach (range(1, 12) as $mois) {
                $plages[$mois] = $moisLibelles[$mois];
            }
        }
        return $plages;
    }


    private function calculerRapport($annee, $plages, $periode)
    {
        Log::info("Début du calcul du rapport périodique.");
        $rapport = [];
        $totalEntreesAcc = 0;
        $previousStockFinal = 0;

        $dateDebutAnnee = Carbon::create($annee, 1, 1)->startOfYear();

        $entreesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum('m.qte');

        $sortiesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum('m.qte');

        $retoursAvantAnnee = DB::table('retour_tickets')
            ->where('created_at', '<', $dateDebutAnnee)
            ->sum('qte');

        $stockInitialDebutAnnee = $entreesAvantAnnee - $sortiesAvantAnnee + $retoursAvantAnnee;
        $stockInitial = $stockInitialDebutAnnee;

        Log::info('Stock initial avant l\'année ' . $annee . ' : ' . $stockInitial);

        foreach ($plages as $index => $label) {
            $moisDebut = 0;
            $moisFin = 0;

            if ($periode === 'trimestriel') {
                $moisDebut = ($index - 1) * 3 + 1;
                $moisFin = $moisDebut + 2;
            } elseif ($periode === 'semestriel') {
                $moisDebut = ($index - 1) * 6 + 1;
                $moisFin = $moisDebut + 5;
            } else { // 'mensuel'
                $moisDebut = $index;
                $moisFin = $index;
            }

            $dateDebut = Carbon::create($annee, $moisDebut, 1)->startOfMonth();
            $dateFin   = Carbon::create($annee, $moisFin, 1)->endOfMonth();

            Log::info('Traitement de la période: ' . $label, ['dates' => [$dateDebut, $dateFin]]);

            if ($index > 1) {
                $stockInitial = $previousStockFinal;
            } else {
                $stockInitial = $stockInitialDebutAnnee;
            }

            Log::info('Stock initial pour cette période : ' . $stockInitial);

            // Calculer les entrées de la période
            $entrees = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum('m.qte');

            // Calculer les sorties de la période
            $sorties = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum('m.qte');

            // Calculer les sorties par catégorie pour cette période
            $categories = DB::table('categorie_sortie_tickets')->pluck('libelle', 'id');
            $sortiesParCategorie = [];
            foreach ($categories as $id => $libelle) {
                $sortiesParCategorie[$libelle] = DB::table('mouvement_tickets as m')
                    ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                    ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                    ->where('m.id_categorie_sortie_ticket', $id)
                    ->whereBetween('m.date', [$dateDebut, $dateFin])
                    ->sum('m.qte');
            }

            // Calculer les retours de la période
            $retours = DB::table('retour_tickets')
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->sum('qte');

            // Calculer le stock final de la période
            $stockFinal = $stockInitial + $entrees - $sorties + $retours;
            $totalEntreesAcc += $entrees;

            Log::info('Calculs pour la période ' . $label, [
                'entrees' => $entrees,
                'sorties' => $sorties,
                'retours' => $retours,
                'stock_final' => $stockFinal
            ]);

            $previousStockFinal = $stockFinal;

            $rapport[] = [
                'periode' => $label,
                'stock_initial' => $stockInitial,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'sorties_par_categorie' => $sortiesParCategorie,
                'retours' => $retours,
                'stock_final' => $stockFinal,
                'total_entrees_cumulees' => $totalEntreesAcc,
            ];
        }

        Log::info("Fin du calcul du rapport périodique.");
        return $rapport;
    }

    private function calculerRapportMontant($annee, $plages, $periode)
    {
        Log::info("Début du calcul du rapport périodique (montants).");
        $rapport = [];
        $totalEntreesAcc = 0;
        $previousStockFinal = 0;

        $dateDebutAnnee = Carbon::create($annee, 1, 1)->startOfYear();

        // Stock initial (en montant)
        $entreesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
            ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum(DB::raw('m.qte * c.valeur'));

        $sortiesAvantAnnee = DB::table('mouvement_tickets as m')
            ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
            ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
            ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
            ->where('m.date', '<', $dateDebutAnnee)
            ->sum(DB::raw('m.qte * c.valeur'));

        $retoursAvantAnnee = DB::table('retour_tickets as r')
            ->join('coupon_tickets as c', 'r.coupon_ticket_id', '=', 'c.id')
            ->where('r.created_at', '<', $dateDebutAnnee)
            ->sum(DB::raw('r.qte * c.valeur'));

        $stockInitialDebutAnnee = $entreesAvantAnnee - $sortiesAvantAnnee + $retoursAvantAnnee;
        $stockInitial = $stockInitialDebutAnnee;

        Log::info('Stock initial (montant) avant l\'année ' . $annee . ' : ' . $stockInitial);

        foreach ($plages as $index => $label) {
            if ($periode === 'trimestriel') {
                $moisDebut = ($index - 1) * 3 + 1;
                $moisFin = $moisDebut + 2;
            } elseif ($periode === 'semestriel') {
                $moisDebut = ($index - 1) * 6 + 1;
                $moisFin = $moisDebut + 5;
            } else { // mensuel
                $moisDebut = $index;
                $moisFin = $index;
            }

            $dateDebut = Carbon::create($annee, $moisDebut, 1)->startOfMonth();
            $dateFin   = Carbon::create($annee, $moisFin, 1)->endOfMonth();

            Log::info('Traitement de la période: ' . $label, ['dates' => [$dateDebut, $dateFin]]);

            $stockInitial = ($index > 1) ? $previousStockFinal : $stockInitialDebutAnnee;
            Log::info('Stock initial (montant) pour cette période : ' . $stockInitial);

            // Entrées (en montant)
            $entrees = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
                ->where('t.libelle_type_mouvement', 'Entrée de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum(DB::raw('m.qte * c.valeur'));

            // Sorties (en montant)
            $sorties = DB::table('mouvement_tickets as m')
                ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
                ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                ->whereBetween('m.date', [$dateDebut, $dateFin])
                ->sum(DB::raw('m.qte * c.valeur'));

            // Sorties par catégorie (en montant)
            $categories = DB::table('categorie_sortie_tickets')->pluck('libelle', 'id');
            $sortiesParCategorie = [];
            foreach ($categories as $id => $libelle) {
                $sortiesParCategorie[$libelle] = DB::table('mouvement_tickets as m')
                    ->join('type_mouvements as t', 'm.id_type_mouvement', '=', 't.id')
                    ->join('coupon_tickets as c', 'm.coupon_ticket_id', '=', 'c.id')
                    ->where('t.libelle_type_mouvement', 'Sortie de Ticket')
                    ->where('m.id_categorie_sortie_ticket', $id)
                    ->whereBetween('m.date', [$dateDebut, $dateFin])
                    ->sum(DB::raw('m.qte * c.valeur'));
            }

            // Retours (en montant)
            $retours = DB::table('retour_tickets as r')
                ->join('coupon_tickets as c', 'r.coupon_ticket_id', '=', 'c.id')
                ->whereBetween('r.created_at', [$dateDebut, $dateFin])
                ->sum(DB::raw('r.qte * c.valeur'));

            // Stock final (en montant)
            $stockFinal = $stockInitial + $entrees - $sorties + $retours;
            $totalEntreesAcc += $entrees;

            Log::info('Calculs (montants) pour la période ' . $label, [
                'entrees' => $entrees,
                'sorties' => $sorties,
                'retours' => $retours,
                'stock_final' => $stockFinal
            ]);

            $previousStockFinal = $stockFinal;

            $rapport[] = [
                'periode' => $label,
                'stock_initial' => $stockInitial,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'sorties_par_categorie' => $sortiesParCategorie,
                'retours' => $retours,
                'stock_final' => $stockFinal,
                'total_entrees_cumulees' => $totalEntreesAcc,
            ];
        }

        Log::info("Fin du calcul du rapport périodique (montants).");
        return $rapport;
    }


    // Fonctions utilitaires à ajouter à la classe du contrôleur
    private function getMoisPourTrimestre($trimestre) {
        switch ($trimestre) {
            case 1: return [1, 2, 3];
            case 2: return [4, 5, 6];
            case 3: return [7, 8, 9];
            case 4: return [10, 11, 12];
            default: return [];
        }
    }

    private function getMoisPourSemestre($semestre) {
        switch ($semestre) {
            case 1: return [1, 2, 3, 4, 5, 6];
            case 2: return [7, 8, 9, 10, 11, 12];
            default: return [];
        }
    }



}
