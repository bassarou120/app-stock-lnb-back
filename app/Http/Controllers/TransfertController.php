<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transfert;
use App\Models\Immobilisation;
use App\Models\Parametrage\StatusImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Ajout pour les transactions
use Illuminate\Support\Facades\Auth; // Ajout pour l'utilisateur connecté
use App\Models\LogJournalisation; // Ajout du modèle de journalisation
use Illuminate\Validation\ValidationException;

class TransfertController extends Controller
{
    public function index(Request $request)
    {
        $transferts = Transfert::with([
            'immobilisation',
            'old_bureau',
            'bureau',
            'old_employe',
            'employe',
        ])
        ->where('isdeleted', false)
        ->latest()->paginate(1000);

        LogJournalisation::create([
                'action'     => "Consultation de la liste des transferts",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

        return new PostResource(true, 'Liste des transferts', $transferts);
    }


    // Créer une nouveau transfert
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'immo_id' => 'required|exists:immobilisations,id',
            'bureau_id' => 'required|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'etat' => 'nullable|string|max:100',
            'date_mouvement' => 'required|date',
            'observation' => 'nullable|string|max:255',
            'date_mise_en_service' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (création transfert)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => json_encode($validator->errors())
            ]);
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $immo = Immobilisation::findOrFail($request->immo_id);

            // Sauvegarder l'ancien bureau/employé AVANT de les modifier
            $oldBureau = $immo->bureau_id;
            $oldEmploye = $immo->employe_id;

            $transfert = Transfert::create([
                'immo_id' => $request->immo_id,
                'old_bureau_id' => $oldBureau,
                'bureau_id' => $request->bureau_id,
                'old_employe_id' => $oldEmploye,
                'employe_id' => $request->employe_id,
                'date_mouvement' => $request->date_mouvement,
                'observation' => $request->observation,
            ]);

            // Mise à jour de l'immobilisation
            $immo->bureau_id = $request->bureau_id;
            $immo->employe_id = $request->employe_id;

            // Si ancienne affectation inexistante, on est dans le cas d'une première mise en service
            if (is_null($oldBureau) && is_null($oldEmploye)) {
                $immo->date_mise_en_service = $request->date_mise_en_service;
            }

            // Statut de l'immobilisation
            if (is_null($request->employe_id)) {
                $statusStock = StatusImmo::where('libelle_status_immo', 'En magasin')->first();
                $immo->id_status_immo = $statusStock?->id;
                $immo->etat = $request->etat;
            } else {
                $statusEnService = StatusImmo::where('libelle_status_immo', 'En service')->first();
                $immo->id_status_immo = $statusEnService?->id;
            }

            $immo->save();

            DB::commit();

            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création transfert réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Transfert ID: {$transfert->id}, Immo ID: {$immo->id}, De Bureau: {$oldBureau} à {$request->bureau_id}, Employé: {$oldEmploye} à {$request->employe_id}"
            ]);

            return new PostResource(true, 'Transfert ou retour enregistré avec succès', $transfert);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Création échouée (exception)
            LogJournalisation::create([
                'action'     => 'Création transfert échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de l\'enregistrement du transfert: ' . $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'immo_id' => 'required|exists:immobilisations,id',
            'bureau_id' => 'required|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'etat' => 'nullable|string|max:100',
            'date_mouvement' => 'required|date',
            'observation' => 'nullable|string|max:255',
            'date_mise_en_service' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (mise à jour transfert)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "ID: {$id}. Erreurs: " . json_encode($validator->errors())
            ]);
            return response()->json($validator->errors(), 422);
        }

        $transfert = Transfert::find($id);
        if (!$transfert) {
            // 📝 LOG → Échec mise à jour (non trouvé)
            LogJournalisation::create([
                'action'     => 'Échec mise à jour transfert (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "Transfert ID: {$id} introuvable."
            ]);
            return response()->json(['message' => 'Transfert introuvable'], 404);
        }

        $oldTransfertData = $transfert->toJson();
        $immo = Immobilisation::findOrFail($request->immo_id);

        DB::beginTransaction();

        try {
            // Sauvegarder les anciens du TRANSFERT AVANT de le modifier pour le log
            $oldBureauTransfert = $transfert->bureau_id;
            $oldEmployeTransfert = $transfert->employe_id;

            // Mise à jour du transfert
            $transfert->update([
                // 'immo_id' est requis par validation, mais ne devrait pas changer
                'bureau_id' => $request->bureau_id,
                'employe_id' => $request->employe_id,
                'date_mouvement' => $request->date_mouvement,
                'observation' => $request->observation,
            ]);

            // Mise à jour de l’immobilisation
            $immo->bureau_id = $request->bureau_id;
            $immo->employe_id = $request->employe_id;

            // Première mise en service ? (Basé sur les anciennes valeurs du transfert mis à jour)
            if (is_null($oldBureauTransfert) && is_null($oldEmployeTransfert)) {
                $immo->date_mise_en_service = $request->date_mise_en_service;
            }

            // Déterminer le statut de l'immobilisation
            if (is_null($request->employe_id)) {
                $statusStock = StatusImmo::where('libelle_status_immo', 'En magasin')->first();
                $immo->id_status_immo = $statusStock?->id;
                $immo->etat = $request->etat;
            } else {
                $statusEnService = StatusImmo::where('libelle_status_immo', 'En service')->first();
                $immo->id_status_immo = $statusEnService?->id;
            }

            $immo->save();

            DB::commit();

            // 📝 LOG → Mise à jour réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour transfert réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "ID: {$id}, Immo ID: {$immo->id}. Anciennes données (Transfert): {$oldTransfertData}. Nouveaux ID: Bureau {$request->bureau_id}, Employé {$request->employe_id}."
            ]);

            return new PostResource(true, 'Transfert modifié avec succès', $transfert);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Mise à jour échouée (exception)
            LogJournalisation::create([
                'action'     => 'Mise à jour transfert échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => "ID: {$id}. Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de la modification du transfert: ' . $e->getMessage()], 500);
        }
    }


    public function destroy(Transfert $transfert, Request $request) // Ajout de Request pour la journalisation
    {
        // 1. Récupérer l'immobilisation concernée
        $immo = Immobilisation::find($transfert->immo_id);
        $detailsLog = "Transfert ID: {$transfert->id}, Immo ID: {$transfert->immo_id}";

        DB::beginTransaction();

        try {
            if ($immo) {
                // 2. Restaurer les anciennes valeurs de l'immobilisation
                $immo->bureau_id = $transfert->old_bureau_id;
                $immo->employe_id = $transfert->old_employe_id;

                // Récupérer les statuts
                $statusEnService = StatusImmo::where('libelle_status_immo', 'En service')->first();
                $statusEnMagasin = StatusImmo::where('libelle_status_immo', 'En magasin')->first();

                // Déterminer le nouveau statut de l'immobilisation après restauration
                $newStatusId = null;
                if (empty($transfert->old_bureau_id) && empty($transfert->old_employe_id)) {
                    // Si l'ancienne affectation était vide, on retourne à "En magasin"
                    $newStatusId = $statusEnMagasin ? $statusEnMagasin->id : null;
                } else {
                    // Sinon, on retourne à "En service"
                    $newStatusId = $statusEnService ? $statusEnService->id : null;
                }
                $immo->id_status_immo = $newStatusId;

                $immo->save();
            } else {
                // 📝 LOG → Immo non trouvée
                LogJournalisation::create([
                    'action'     => 'Avertissement suppression transfert (Immo manquante)',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => $request->user()->id,
                    'user_name'   => $request->user()->name,
                    'date_action'=> now(),
                    //'details'    => $detailsLog . ". Immobilisation associée non trouvée pour restauration."
                ]);
            }

            // 3. Supprimer logiquement le transfert
            $transfert->isdeleted = true;
            $transfert->save();

            DB::commit();

            // 📝 LOG → Suppression (Annulation) réussie
            LogJournalisation::create([
                'action'     => 'Suppression (Annulation) transfert réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => $detailsLog . ". Immo restaurée (Bureau: {$transfert->old_bureau_id}, Employé: {$transfert->old_employe_id}, Statut: {$newStatusId})."
            ]);

            return new PostResource(true, 'Transfert supprimé et immobilisation restaurée avec succès', null);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Suppression (Annulation) échouée (exception)
            LogJournalisation::create([
                'action'     => 'Suppression (Annulation) transfert échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
                //'details'    => $detailsLog . ". Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de la suppression du transfert: ' . $e->getMessage()], 500);
        }
    }


    // --- (Reste des méthodes de lecture, d'impression et utilitaires non modifiées) ---

    public function getOldInfo($idImmo)
    {
        $immo = Immobilisation::with(['bureau', 'employe'])->where('isdeleted', false)->find($idImmo);
        if (!$immo) {
            return response()->json(['message' => 'Immobilisation non trouvée'], 404);
        }

        return response()->json([
            'bureau_id' => $immo->bureau_id,
            'employe_id' => $immo->employe_id,
            'bureau' => $immo->bureau ? $immo->bureau->libelle_bureau : null,
            'employe' => $immo->employe ? $immo->employe->nom . ' ' . $immo->employe->prenom : null
        ]);
    }

    public function imprimerTransferts(Request $request)
    {
        // Récupère tous les transferts avec leurs relations nécessaires
        $transferts = Transfert::with([
            'immobilisation',
            'old_bureau',
            'bureau',
            'old_employe',
            'employe'
        ])
        ->where('isdeleted', false)
        ->latest()->get();

        // Charge la vue Blade qui servira de template pour le PDF
        $pdf = \Pdf::loadView('pdf.transferts', compact('transferts'));

        LogJournalisation::create([
                'action'     => "Impression de la liste des transferts",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

        // Retourne le PDF en téléchargement
        return $pdf->download('liste_transferts.pdf');
    }

    public function printSingleTransfert($id)
    {
        $transfert = Transfert::with(['immobilisation', 'old_bureau', 'bureau', 'old_employe', 'employe'])
                            ->find($id);
        if (!$transfert) {
            return response()->json(['message' => 'Transfert introuvable'], 404);
        }

        $data = ['transfert' => $transfert];
        $pdf = \Pdf::loadView('pdf.single_transfert', $data);

        return $pdf->download('transfert_' . $id . '.pdf');
    }
}