<?php

namespace App\Http\Controllers;

use App\Models\ExerciceMouvementTicket;
use App\Models\Article;
use App\Models\Exercice;
use App\Http\Resources\PostResource;
use App\Models\MouvementTicket;
use App\Models\Parametrage\CouponTicket;
use App\Models\Parametrage\CompagniePetrolier;
use Illuminate\Http\Request;
use App\Models\LogJournalisation;
use Illuminate\Support\Facades\Auth;

class ExerciceMouvementTicketController extends Controller
{
    public function index(Request $request)
    {
        try {
            $associations = ExerciceMouvementTicket::with(['exercice', 'couponTicket', 'compagniePetrolier'])->get();

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Consultation liste ExerciceMouvementTicket',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Liste récupérée avec succès.',
                'data'    => $associations,
            ]);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur consultation ExerciceMouvementTicket : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_exercice'  => 'required|exists:exercices,id',
                'id_mouvement' => 'required|exists:mouvement_tickets,id',
            ]);

            $exerciceMouvementTicket = ExerciceMouvementTicket::create($request->all());

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Création association ExerciceMouvementTicket',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Association créée avec succès.', $exerciceMouvementTicket);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur création ExerciceMouvementTicket : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    public function show(Request $request, $exerciceId, $mouvementId)
    {
        try {
            $exerciceMouvementTicket = ExerciceMouvementTicket::where('id_exercice', $exerciceId)
                ->where('id_mouvement', $mouvementId)
                ->firstOrFail();

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Consultation association ID Exercice=' . $exerciceId . ' / Mouvement=' . $mouvementId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Association trouvée.', $exerciceMouvementTicket);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur consultation association Exercice/Mouvement : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $exerciceMouvementTicket = ExerciceMouvementTicket::findOrFail($id);
            $exerciceMouvementTicket->update($request->all());

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Modification association ExerciceMouvementTicket ID=' . $id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Association mise à jour.', $exerciceMouvementTicket);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur modification ExerciceMouvementTicket ID=' . $id . ' : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    public function destroy(Request $request, $id)
    {
        try {
            $exerciceMouvementTicket = ExerciceMouvementTicket::findOrFail($id);
            $exerciceMouvementTicket->delete();

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Suppression association ExerciceMouvementTicket ID=' . $id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Association supprimée avec succès.');

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur suppression ExerciceMouvementTicket ID=' . $id . ' : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }
}
