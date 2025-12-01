<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SortiePatrimoine;
use App\Http\Resources\PostResource;
use App\Models\Vehicule;
use App\Models\Immobilisation;
use App\Models\Exercice;
use App\Models\LogJournalisation; // Ajout du modèle de journalisation
use Illuminate\Support\Facades\Auth; // Ajout pour récupérer l'ID utilisateur
use Illuminate\Support\Facades\DB;
use App\Models\Parametrage\StatusImmo;
use Illuminate\Validation\ValidationException;


class SortiePatrimoineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Trouver l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Veuillez ouvrir un exercice pour consulter les sorties de patrimoine.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        // 2. Filtrer les sorties de patrimoine par l'ID de l'exercice OUVERT
        $sortiespatrimoines = SortiePatrimoine::where('isdeleted', false)
            ->where('exercice_id', $exerciceId) // <-- C'est ici qu'on ajoute le filtre
            ->latest()
            ->paginate(1000); 

            LogJournalisation::create([
                'action'     => "Consultation des sorties de patrimoine",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

        // 3. Retourner la réponse
        return new PostResource(true, 'Liste des sorties de patrimoine pour l\'exercice ouvert', $sortiespatrimoines);
    }

    /**
     * Store a newly created resource in storage (Single entry).
     */
    public function store(Request $request)
    {
        // 1. Trouver l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Impossible d\'enregistrer la sortie.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        try {
            // 2. Validation des données
            $validatedData = $request->validate([
                'code_immo'      => 'required|string|max:50',
                'designation_immo' => 'required|string|max:50',
                'type_immo'      => 'required|string|max:50',
                'valeur'          => 'required|numeric|min:0',
                'date_sortie'    => 'required|date_format:Y-m-d',
                'observation'    => 'nullable|string',
            ]);

            // 3. Création de l'enregistrement
            $sortiePatrimoine = SortiePatrimoine::create(array_merge($validatedData, [
                'exercice_id' => $exerciceId, // Liaison à l'exercice ouvert
                'isdeleted'   => false,
            ]));

            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création sortie patrimoine réussie (simple)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Sortie ID: {$sortiePatrimoine->id}, Code: {$validatedData['code_immo']}"
            ]);

            // 4. Retourner la réponse
            return new PostResource(true, 'Sortie de patrimoine enregistrée avec succès.', $sortiePatrimoine);

        } catch (ValidationException $e) {
            // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (création sortie patrimoine)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => json_encode($e->errors())
            ]);
            throw $e;
        } catch (\Exception $e) {
            // 📝 LOG → Création échouée (exception)
            LogJournalisation::create([
                'action'     => 'Création sortie patrimoine échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Erreur: " . $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la sortie de patrimoine.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage (Batch).
     */
    public function storeBatch(Request $request)
    {
        // 1. Trouver l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Veuillez ouvrir un exercice pour enregistrer les sorties.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        try {
            // 2. Validation des données
            $validatedData = $request->validate([
                'sorties' => 'required|array|min:1',
                'sorties.*.code_immo' => 'required|string|max:50',
                'sorties.*.designation_immo' => 'required|string|max:50',
                'sorties.*.type_immo' => 'required|string|max:50',
                'sorties.*.valeur' => 'required|numeric|min:0',
                'sorties.*.date_sortie' => 'required|date_format:Y-m-d',
                'sorties.*.observation' => 'nullable|string',
            ]);
            
            // 3. Récupérer l'ID du statut "Sortie de patrimoine"
            $statusSortie = StatusImmo::where('libelle_status_immo', 'Sortie de patrimoine')->first();

            if (!$statusSortie) {
                // 📝 LOG → Échec (statut manquant)
                LogJournalisation::create([
                    'action'     => 'Échec création sortie patrimoine (statut manquant)',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => Auth::id(),
                    'date_action'=> now(),
                    'details'    => "Le statut 'Sortie de patrimoine' est introuvable."
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Le statut "Sortie de patrimoine" n\'existe pas. Veuillez le créer dans la table status_immo.'
                ], 404);
            }

            // 4. Préparation des données pour insertion
            $sortiesToInsert = [];
            $codesImmoUpdated = [];
            $timestamp = now();

            DB::beginTransaction();
            
            foreach ($validatedData['sorties'] as $sortie) {
                // Insertion dans la table SortiePatrimoine
                $sortiesToInsert[] = [
                    'code_immo'      => $sortie['code_immo'],
                    'designation_immo' => $sortie['designation_immo'],
                    'type_immo'      => $sortie['type_immo'],
                    'valeur'           => $sortie['valeur'],
                    'date_sortie'      => $sortie['date_sortie'],
                    'observation'      => $sortie['observation'] ?? null,
                    'exercice_id'      => $exerciceId,
                    'isdeleted'        => false,
                    'created_at'       => $timestamp,
                    'updated_at'       => $timestamp,
                ];

                // === Mise à jour du statut selon le type d'immo ===
                if (strtolower($sortie['type_immo']) === 'vehicule') {
                    Vehicule::where('code', $sortie['code_immo'])
                        ->update(['id_status_immo' => $statusSortie->id]);
                    $codesImmoUpdated[] = "V:" . $sortie['code_immo'];
                } else {
                    Immobilisation::where('code', $sortie['code_immo'])
                        ->update(['id_status_immo' => $statusSortie->id]);
                    $codesImmoUpdated[] = "I:" . $sortie['code_immo'];
                }
            }

            // Insertion en masse
            SortiePatrimoine::insert($sortiesToInsert);

            DB::commit();

            // 📝 LOG → Création réussie (Batch)
            LogJournalisation::create([
                'action'     => 'Création sortie patrimoine réussie (Batch)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => count($sortiesToInsert) . " sorties enregistrées. Codes Immo: " . implode(', ', $codesImmoUpdated)
            ]);

            return new PostResource(true, count($sortiesToInsert) . ' sorties de patrimoine enregistrées avec succès.', null);

        } catch (ValidationException $e) {
            // 📝 LOG → Échec de validation (Batch)
            LogJournalisation::create([
                'action'     => 'Échec validation (création sortie patrimoine Batch)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => json_encode($e->errors())
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Création échouée (exception Batch)
            LogJournalisation::create([
                'action'     => 'Création sortie patrimoine échouée (exception Batch)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement multiple des sorties.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // 1. Trouver l'enregistrement
        $sortiePatrimoine = SortiePatrimoine::find($id);

        // 2. Vérifier si l'enregistrement existe
        if (!$sortiePatrimoine || $sortiePatrimoine->isdeleted) {
            return response()->json([
                'success' => false,
                'message' => 'Sortie de patrimoine non trouvée ou supprimée.'
            ], 404);
        }

        // 4. Retourner la réponse
        return new PostResource(true, 'Détails de la sortie de patrimoine.', $sortiePatrimoine);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // 1. Trouver l'enregistrement
        $sortiePatrimoine = SortiePatrimoine::find($id);

        if (!$sortiePatrimoine) {
            // 📝 LOG → Échec mise à jour (non trouvé)
            LogJournalisation::create([
                'action'     => 'Échec mise à jour sortie patrimoine (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id} non trouvé."
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sortie de patrimoine non trouvée.'
            ], 404);
        }
        
        $oldData = $sortiePatrimoine->toJson();

        try {
            // 2. Validation des données
            $validatedData = $request->validate([
                'code_immo'      => 'sometimes|required|string|max:50',
                'designation_immo' => 'sometimes|required|string|max:50',
                'type_immo'      => 'sometimes|required|string|max:50',
                'valeur'          => 'sometimes|required|numeric|min:0',
                'date_sortie'    => 'sometimes|required|date_format:Y-m-d',
                'observation'    => 'nullable|string',
            ]);

            // 3. Mise à jour de l'enregistrement
            $sortiePatrimoine->fill($validatedData);
            $sortiePatrimoine->save();

            // 📝 LOG → Mise à jour réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour sortie patrimoine réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id}. Anciennes données: {$oldData}. Nouvelles données: " . $sortiePatrimoine->toJson()
            ]);

            // 4. Retourner la réponse
            return new PostResource(true, 'Sortie de patrimoine mise à jour avec succès.', $sortiePatrimoine);

        } catch (ValidationException $e) {
             // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (mise à jour sortie patrimoine)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id}. Erreurs: " . json_encode($e->errors())
            ]);
            throw $e;
        } catch (\Exception $e) {
            // 📝 LOG → Mise à jour échouée (exception)
            LogJournalisation::create([
                'action'     => 'Mise à jour sortie patrimoine échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id}. Erreur: " . $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la sortie de patrimoine.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Annulation de sortie).
     */
    public function destroy($id, Request $request) // Ajout de Request pour la journalisation
    {
        // 1. Trouver l'enregistrement
        $sortiePatrimoine = SortiePatrimoine::find($id);

        if (!$sortiePatrimoine) {
            // 📝 LOG → Échec suppression (non trouvé)
            LogJournalisation::create([
                'action'     => 'Échec suppression sortie patrimoine (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "ID: {$id} non trouvé."
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sortie de patrimoine non trouvée.'
            ], 404);
        }

        $codeImmo = $sortiePatrimoine->code_immo;
        $typeImmo = $sortiePatrimoine->type_immo;
        $detailsLog = "ID: {$id}, Code Immo: {$codeImmo}, Type: {$typeImmo}";

        try {
            DB::beginTransaction();
            
            // 🔹 Récupérer l'ID du statut "En magasin"
            $statusEnMagasin = StatusImmo::where('libelle_status_immo', 'En magasin')->first();

            if (!$statusEnMagasin) {
                 // 📝 LOG → Échec (statut manquant)
                LogJournalisation::create([
                    'action'     => 'Échec annulation sortie patrimoine (statut manquant)',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => Auth::id(),
                    'date_action'=> now(),
                    'details'    => $detailsLog . ". Le statut 'En magasin' est introuvable."
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Le statut "En magasin" est introuvable.'
                ], 500);
            }

            // 🔹 Vérifier si c’est un véhicule ou une immobilisation et mettre à jour le statut
            $updated = false;
            if (strtolower($typeImmo) === 'vehicule') {
                $vehicule = Vehicule::where('code', $codeImmo)->first();
                if ($vehicule) {
                    $vehicule->id_status_immo = $statusEnMagasin->id;
                    $vehicule->save();
                    $updated = true;
                }
            } else {
                $immobilisation = Immobilisation::where('code', $codeImmo)->first();
                if ($immobilisation) {
                    $immobilisation->id_status_immo = $statusEnMagasin->id;
                    $immobilisation->save();
                    $updated = true;
                }
            }
            
            if (!$updated) {
                 // 📝 LOG → Échec (Actif non trouvé)
                LogJournalisation::create([
                    'action'     => 'Échec annulation sortie patrimoine (actif non trouvé)',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => Auth::id(),
                    'date_action'=> now(),
                    'details'    => $detailsLog . ". Actif ({$codeImmo}) introuvable dans Vehicules/Immobilisations pour mise à jour."
                ]);
            }

            // 🔹 Supprimer l'entrée dans SortiePatrimoine (Suppression physique : $sortiePatrimoine->delete();)
            $sortiePatrimoine->delete();

            DB::commit();

            // 📝 LOG → Suppression (Annulation) réussie
            LogJournalisation::create([
                'action'     => 'Annulation sortie patrimoine réussie (Suppression physique)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $detailsLog . ". Statut actif mis à jour vers 'En magasin'."
            ]);

            return new PostResource(true, 'Sortie de patrimoine annulée, actif remis en magasin et retrait de la table SortiePatrimoine.', null);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // 📝 LOG → Suppression échouée (exception)
            LogJournalisation::create([
                'action'     => 'Annulation sortie patrimoine échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $detailsLog . ". Erreur: " . $e->getMessage()
            ]);
            
            \Log::error("Erreur lors de l'annulation de la sortie de patrimoine #{$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’annulation de la sortie de patrimoine.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        //
    }
    
    public function edit(string $id)
    {
        //
    }
}