<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Parametrage\MouvementTicket;
use App\Models\Parametrage\Commune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Ajout pour récupérer l'ID utilisateur
use App\Http\Resources\PostResource;
use App\Models\LogJournalisation; // Ajout du modèle de journalisation

class TrajetController extends Controller
{
    // Afficher tous les trajets
    public function index(Request $request)
    {
        $trajet = Trajet::with([
            'depart',
            'arriver'
        ])
        ->where('isdeleted', false)
        ->latest()->paginate(1000);
        LogJournalisation::create([
            'action'     => "Consultation des trajets",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        return new PostResource(true, 'Liste des trajets', $trajet);
    }

    // --- Créer un trajet ---
    public function store(Request $request)
    {
        try {
            // Utilisation de validate() qui gère automatiquement les erreurs 422
            $validated = $request->validate([
                'commune_depart' => 'required|exists:communes,id',
                'commune_arriver' => 'required|exists:communes,id',
                'trajet_aller_retour' => 'required|boolean',
                'valeur' => 'required|integer',
                'observation' => 'nullable|string',
            ]);

            $trajet = Trajet::create($validated);
            
            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création de trajet réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Trajet ID: {$trajet->id}, Départ: {$validated['commune_depart']} -> Arrivée: {$validated['commune_arriver']}"
            ]);

            return response()->json($trajet, 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 📝 LOG → Échec de validation (création)
            LogJournalisation::create([
                'action'     => 'Échec de validation (création trajet)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => json_encode($e->errors())
            ]);
            // Renvoyer l'erreur de validation (gérée par le framework si on catch pas, mais pour le log on le fait)
            throw $e; 
            
        } catch (\Exception $e) {
            // 📝 LOG → Création échouée (exception)
            LogJournalisation::create([
                'action'     => 'Création de trajet échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => $e->getMessage()
            ]);
            
            // Log de l'erreur interne
            \Log::error("Erreur lors de la création du trajet: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Erreur lors de la création du trajet.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Afficher un trajet spécifique (pas de log pour lecture simple)
    public function show($id)
    {
        $trajet = Trajet::with(['mouvementTickets', 'depart', 'arriver'])->findOrFail($id);
        return response()->json($trajet);
    }

    // --- Mettre à jour un trajet ---
    public function update(Request $request, $id)
    {
        try {
            $trajet = Trajet::findOrFail($id);
            $oldDetails = "Ancien Trajet ID: {$trajet->id}, Départ: {$trajet->commune_depart}, Arrivée: {$trajet->commune_arriver}, Valeur: {$trajet->valeur}";

            // Utilisation de validate()
            $validated = $request->validate([ 
                'commune_depart' => 'sometimes|required|exists:communes,id',
                'commune_arriver' => 'sometimes|required|exists:communes,id',
                'trajet_aller_retour' => 'sometimes|required|boolean',
                'observation' => 'nullable|string',
                'valeur' => 'required|integer', // La validation `required` ici sur 'valeur' peut être simplifiée en `sometimes|required` si elle n'est pas toujours envoyée. Je garde votre logique originale.
            ]);

            $trajet->update($validated);
            
            // 📝 LOG → Mise à jour réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour de trajet réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => $oldDetails . ". Nouvelles données: " . json_encode($validated)
            ]);

            return response()->json($trajet);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 📝 LOG → Échec de validation (mise à jour)
            LogJournalisation::create([
                'action'     => 'Échec de validation (mise à jour trajet)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Trajet ID: {$id}. Erreurs: " . json_encode($e->errors())
            ]);
            throw $e; 

        } catch (\Exception $e) {
            // 📝 LOG → Mise à jour échouée (exception ou FindOrFail échoué)
            LogJournalisation::create([
                'action'     => 'Mise à jour de trajet échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Trajet ID: {$id}. Erreur: " . $e->getMessage()
            ]);
            
            \Log::error("Erreur lors de la mise à jour du trajet #{$id}: " . $e->getMessage());
            
            $statusCode = ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) ? 404 : 500;
            
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du trajet.', 
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    // --- Supprimer un trajet ---
    public function destroy($id, Request $request)
    {
        try {
            $trajet = Trajet::findOrFail($id);
            $trajet->isdeleted = true;
            $trajet->save();
            
            // 📝 LOG → Suppression réussie
            LogJournalisation::create([
                'action'     => 'Suppression de trajet réussie (soft delete)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Trajet ID: {$trajet->id}, Départ: {$trajet->commune_depart}, Arrivée: {$trajet->commune_arriver}"
            ]);

            return response()->json(['message' => 'Trajet supprimé avec succès']);

        } catch (\Exception $e) {
            // 📝 LOG → Suppression échouée
            LogJournalisation::create([
                'action'     => 'Suppression de trajet échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Trajet ID: {$id}. Erreur: " . $e->getMessage()
            ]);
            
            \Log::error("Erreur lors de la suppression du trajet #{$id}: " . $e->getMessage());
            
            $statusCode = ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) ? 404 : 500;
            
            return response()->json([
                'message' => 'Erreur lors de la suppression du trajet.', 
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }
}