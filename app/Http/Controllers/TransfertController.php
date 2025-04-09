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

            //    'old_bureau_id' => 'nullable|exists:bureaus,id',
            'bureau_id' => 'required|exists:bureaus,id',

            //    'old_employe_id' => 'nullable|exists:employes,id',
            'employe_id' => 'required|exists:employes,id',

            'date_mouvement' => 'required|date',
            'observation' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $immo = Immobilisation::where('id', $request->immo_id)->latest()->first();

        $transfert = Transfert::create([
            'immo_id' => $request->immo_id,
            'old_bureau_id' => $immo->bureau_id,
            'bureau_id' => $request->bureau_id,
            'old_employe_id' => $immo->employe_id,
            'employe_id' => $request->employe_id,
            'date_mouvement' => $request->date_mouvement,
            'observation' => $request->observation,
        ]);

        $statusEnService = StatusImmo::where('libelle_status_immo', 'En service')->first();

        $immo->bureau_id = $request->bureau_id;
        $immo->employe_id = $request->employe_id;
        $immo->id_status_immo = $statusEnService->id;
        $immo->save();

        return new PostResource(true, 'transferts créé avec succès', $transfert);
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


}
