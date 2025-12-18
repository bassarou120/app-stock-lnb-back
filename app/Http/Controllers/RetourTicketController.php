<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RetourTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\StockTicket;
use App\Models\MouvementTicket;
use App\Models\Parametrage\TypeMouvement;
use App\Models\LogJournalisation; // Ajout du modèle de journalisation
use Illuminate\Support\Facades\Auth; // Ajout pour récupérer l'ID utilisateur
use Illuminate\Support\Facades\DB;
use App\Models\Parametrage\CouponTicket;


class RetourTicketController extends Controller
{
    public function index(Request $request)
    {
        $retours = RetourTicket::with([
            'mouvement.employe',
            'mouvement.vehicule',
            'coupon',
            'compagnie'
        ])
        ->latest()
        ->where('isdeleted', false)
        ->paginate(1000);

        LogJournalisation::create([
                'action'     => "Consultation des retours de tickets",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

        return new PostResource(true, 'Liste des retours', $retours);
    }

    public function mouvementsDisponibles()
    {
        $mouvements = MouvementTicket::where('isdeleted', false)
            ->select('id', 'reference')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($mouvements);
    }


    // --- Enregistrer un nouveau retour de ticket ---
    public function store(Request $request)
    {
        // 💡 Le FormArray du Frontend s'appelle 'retours_coupons'
        $validator = Validator::make($request->all(), [
            "retours_coupons" => 'required|array|min:1',

            // Validation des champs de chaque ligne
            "retours_coupons.*.mouvement_ticket_id" => 'required|exists:mouvement_tickets,id',
            "retours_coupons.*.coupon_ticket_id" => 'required|exists:coupon_tickets,id',
            // 'compagnie_petrolier_id' est envoyé comme champ caché pour être utilisé ici
            "retours_coupons.*.compagnie_petrolier_id" => 'required|exists:compagnie_petroliers,id',
            // 'qte_retournee' est le nom du champ de saisie du Frontend (qui devient 'qte' dans la DB)
            "retours_coupons.*.qte_retournee" => 'required|integer|min:1',
            "retours_coupons.*.date_retour" => 'required|date',
        ]);

        dd($request->all());
        echo "Debugging Info:" . $request->all();

        if ($validator->fails()) {
            // 📝 LOG → Échec de validation (création)
            LogJournalisation::create([
                'action'     => 'Échec de validation (création retour ticket)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {

            $retours_ids = [];

            // BOUCLE SUR CHAQUE LIGNE DE COUPON À RETOURNER
            foreach ($request->retours_coupons as $retourData) {

                // 1. CRÉATION DU RETOUR DE TICKET
                $retour = RetourTicket::create([
                    'mouvementTicket_id' => $retourData['mouvement_ticket_id'],
                    'compagnie_petrolier_id' => $retourData['compagnie_petrolier_id'],
                    'coupon_ticket_id' => $retourData['coupon_ticket_id'],
                    'qte' => $retourData['qte_retournee'], // qte_retournee du front -> qte de la DB
                    'date_retour'             => $retourData['date_retour'],
                ]);
                $retours_ids[] = $retour->id;

                // 2. MISE À JOUR DU STOCK (Retour = Augmentation du stock)
                $stockTicket = StockTicket::firstOrCreate(
                    ['coupon_ticket_id' => $retourData['coupon_ticket_id'], 'compagnie_petrolier_id' => $retourData['compagnie_petrolier_id']],
                    ['qte_actuel' => 0]
                );

                $stockTicket->qte_actuel += $retourData['qte_retournee'];
                $stockTicket->save();
            }

            DB::commit();

            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création de retour(s) ticket réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Retour(s) de Tickets enregistré(s) avec succès !', null);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Création échouée (exception)
            LogJournalisation::create([
                'action'     => 'Création de retour(s) ticket échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            \Log::error('Erreur lors de l\'enregistrement du retour de ticket: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'enregistrement du retour: ' . $e->getMessage()], 500);
        }
    }

    // --- Supprimer un retour de ticket ---
    public function destroy($id, Request $request) // Ajout de Request pour la journalisation
    {
        $retourTicket = RetourTicket::find($id);

        if (!$retourTicket) {
            // 📝 LOG → Suppression échouée (non trouvé)
            LogJournalisation::create([
                'action'     => 'Suppression retour ticket échouée (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Mouvement introuvable.'
            ], 404);
        }

        // Stockage des données avant suppression
        $couponId = $retourTicket->coupon_ticket_id;
        $compagnieId = $retourTicket->compagnie_petrolier_id;
        $qteRetournee = $retourTicket->qte;
        $mouvementId = $retourTicket->mouvementTicket_id;
        $detailsLog = "Retour ID: {$id}, Mouvement ID: {$mouvementId}, Coupon ID: {$couponId}, Qté: {$qteRetournee}";

        DB::beginTransaction();
        try {
            // Vérifier si un stock existe pour ce ticket
            $stock = StockTicket::where('coupon_ticket_id', $couponId)
            ->where('compagnie_petrolier_id', $compagnieId)
            ->latest()
            ->where('isdeleted', false)
            ->first();

            if ($stock) {
                // Réduire la quantité du stock
                $stock->qte_actuel -= $qteRetournee;

                // Empêcher que la quantité devienne négative
                if ($stock->qte_actuel < 0) {
                    $stock->qte_actuel = 0;
                }

                $stock->save();
            }

            // Supprimer le retourTicket (Soft Delete)
            $retourTicket->isdeleted = true;
            $retourTicket->save();

            DB::commit();

            // 📝 LOG → Suppression réussie
            LogJournalisation::create([
                'action'     => 'Suppression retour ticket réussie (soft delete)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => $detailsLog
            ]);

            return new PostResource(true, 'Retour Ticket supprimé avec succès !', null);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Suppression échouée (exception)
            LogJournalisation::create([
                'action'     => 'Suppression retour ticket échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => $detailsLog . ". Erreur: " . $e->getMessage()
            ]);

            \Log::error("Erreur lors de la suppression du retour ticket #{$id}: " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la suppression du retour ticket: ' . $e->getMessage()], 500);
        }
    }


    public function getAllSortieTicketWhereNotInRetour(Request $request)
    {
        // Récupérer l'ID du type de mouvement "Sortie de Ticket"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Ticket')->first();

        if ($type_mouvement) {
            // Récupérer les IDs des mouvements qui ont un retour
            $mouvementsAvecRetour = RetourTicket::pluck('mouvementTicket_id')->toArray();

            // Récupérer les mouvements "Sortie de Ticket" qui ne sont pas dans la liste des mouvements avec retour
            $mouvements = MouvementTicket::with(['employe', 'compagniePetrolier', 'vehicule', 'coupon_ticket'])
                ->where('id_type_mouvement', $type_mouvement->id)
                ->where('isdeleted', false)
                ->whereNotIn('id', $mouvementsAvecRetour)
                ->latest()
                ->paginate(1000);

            LogJournalisation::create([
                'action'     => "Consultation des mouvements de sortie de Ticket sans retour",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des mouvements de sortie de Ticket sans retour', $mouvements);
        }

        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Ticket".', []);
    }

    public function getMouvementInfo($idMouvement)//Nouveau
    {
        $mouvementInitial = MouvementTicket::find($idMouvement);

        if (!$mouvementInitial) {
            return response()->json(['message' => 'Mouvement de sortie non trouvé.'], 404);
        }

        // 1. Récupérer TOUTES les lignes de MouvementTicket pour la même référence de sortie.
        $lignesDeSortie = MouvementTicket::with(['coupon_ticket', 'compagniePetrolier'])
                            ->where('reference', $mouvementInitial->reference)
                            ->where('isdeleted', false)
                            ->get();

        $couponsGroupes = [];

        // 2. Regrouper les quantités de sortie par coupon
        foreach ($lignesDeSortie as $ligne) {
            $couponId = $ligne->coupon_ticket_id;

            if (!isset($couponsGroupes[$couponId])) {
                $couponsGroupes[$couponId] = [
                    'mouvementTicket_ids' => [],
                    'quantite_sortie_totale' => 0,
                    'coupon' => $ligne->coupon_ticket,
                    'compagnie_libelle' => $ligne->compagniePetrolier->libelle ?? 'N/A',
                    'compagnie_id' => $ligne->compagnie_petrolier_id,
                ];
            }

            $couponsGroupes[$couponId]['mouvementTicket_ids'][] = $ligne->id;
            $couponsGroupes[$couponId]['quantite_sortie_totale'] += $ligne->qte;
        }

        $couponsDetails = [];

        // 3. Calculer la quantité restante à retourner pour chaque coupon
        foreach ($couponsGroupes as $couponId => $groupe) {

            // Calculer la quantité déjà retournée
            $quantiteRetournee = RetourTicket::whereIn('mouvementTicket_id', $groupe['mouvementTicket_ids'])
                                                ->where('coupon_ticket_id', $couponId)
                                                ->where('isdeleted', false)
                                                ->sum('qte');

            $quantiteMaxRetournable = $groupe['quantite_sortie_totale'] - $quantiteRetournee;

            // On n'affiche que s'il reste une quantité à retourner
            if ($quantiteMaxRetournable > 0) {
                $couponsDetails[] = [
                    // Champs requis pour le PAYLOAD FINAL
                    'mouvement_ticket_id' => $groupe['mouvementTicket_ids'][0],
                    'coupon_ticket_id' => $couponId,
                    'compagnie_petrolier_id' => $groupe['compagnie_id'],

                    // CHAMPS POUR L'AFFICHAGE ET LE FRONTEND
                    'libelle_affichage' => $groupe['coupon']->libelle . ' (' . $groupe['compagnie_libelle'] . ')', // Pour l'affichage "Coupon (Compagnie)"
                    'quantite_max_retournable' => $quantiteMaxRetournable,
                ];
            }
        }

        return response()->json([
            'compagnie_petrolier_id' => $mouvementInitial->compagnie_petrolier_id,
            'coupons_details' => $couponsDetails,
        ]);
    }
}
