<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transfert;
use App\Models\Immobilisation;
use App\Models\Parametrage\StatusImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class TransfertController extends Controller
{
    public function index()
    {
        $transferts = Transfert::with([
            'immobilisation',
            'old_bureau',
            'bureau',
            'old_employe',
            'employe',
        ])->latest()->paginate(1000);

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
        'date_mise_en_service' => 'nullable|date', // ✅ Champ ajouté ici
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

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

    // ✅ Si ancienne affectation inexistante, on est dans le cas d'une première mise en service
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

    return new PostResource(true, 'Transfert ou retour enregistré avec succès', $transfert);
}




    public function destroy(Transfert $transfert)
    {
        // Récupérer l'immobilisation concernée
        $immo = Immobilisation::find($transfert->immo_id);

        if ($immo) {
            // Restaurer les anciennes valeurs de l'immobilisation
            $immo->bureau_id = $transfert->old_bureau_id;
            $immo->employe_id = $transfert->old_employe_id;

            // Récupérer les statuts
            $statusEnService = StatusImmo::where('libelle_status_immo', 'En service')->first();
            $statusEnMagazin = StatusImmo::where('libelle_status_immo', 'En magazin')->first();

            // Vérifier si old_bureau_id ou old_employe_id est null ou ""
            if (empty($transfert->old_bureau_id) || empty($transfert->old_employe_id)) {
                $immo->id_status_immo = $statusEnMagazin ? $statusEnMagazin->id : null;
            } else {
                $immo->id_status_immo = $statusEnService ? $statusEnService->id : null;
            }

            $immo->save();
        }


        // Supprimer le transfert
        $transfert->delete();

        return new PostResource(true, 'Transfert supprimé et immobilisation restaurée avec succès', null);
    }


    public function getOldInfo($idImmo)
    {
        $immo = Immobilisation::with(['bureau', 'employe'])->find($idImmo);
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

    public function imprimerTransferts()
    {
        // Récupère tous les transferts avec leurs relations nécessaires
        $transferts = Transfert::with([
            'immobilisation',
            'old_bureau',
            'bureau',
            'old_employe',
            'employe'
        ])->latest()->get();

        // Charge la vue Blade qui servira de template pour le PDF
        // Utilise l'alias global pour la façade Pdf (\Pdf)
        $pdf = \Pdf::loadView('pdf.transferts', compact('transferts'));

        // Retourne le PDF en téléchargement
        return $pdf->download('liste_transferts.pdf'); // Nom du fichier PDF à télécharger
    }
}
