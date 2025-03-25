<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class VehiculeController extends Controller
{
     // Afficher la liste des véhicules
     public function index()
     {
         $vehicules = Vehicule::with(['modele', 'marque'])->latest()->paginate(1000);
         return new PostResource(true, 'Liste des véhicules', $vehicules);
     }

     public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicules' => 'required|array',
            'vehicules.*.marque_id' => 'required|exists:marques,id',
            'vehicules.*.modele_id' => 'required|exists:modeles,id',
            'vehicules.*.immatriculation' => 'required|string|max:255',
            'vehicules.*.numero_chassis' => 'required|string|max:255',
            'vehicules.*.kilometrage' => 'required|integer',
            'vehicules.*.date_mise_en_service' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $vehicules = [];

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            foreach ($request->vehicules as $vehiculeData) {
                $vehicule = Vehicule::create([
                    'marque_id' => $vehiculeData['marque_id'],
                    'modele_id' => $vehiculeData['modele_id'],
                    'immatriculation' => $vehiculeData['immatriculation'],
                    'numero_chassis' => $vehiculeData['numero_chassis'],
                    'kilometrage' => $vehiculeData['kilometrage'],
                    'date_mise_en_service' => $vehiculeData['date_mise_en_service'],
                ]);



                $vehicules[] = $vehicule;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors de l\'enregistrement des vehicule.'], 500);
        }

        return new PostResource(true, count($vehicules) . ' vehicules créés avec succès', $vehicules);
    }


    // Mettre à jour un véhicule existant
    public function update(Request $request, Vehicule $vehicule)
    {
        $validator = Validator::make($request->all(), [
            'marque_id' => 'required|exists:marques,id',
            'modele_id' => 'required|exists:modeles,id',
            'immatriculation' => 'required|string|max:255',
            'numero_chassis' => 'required|string|max:255',
            'kilometrage' => 'required|integer',
            'date_mise_en_service' => 'required',
        ]);

        // Log::info($request->all());


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $vehicule->update([
            'marque_id' => $request->marque_id,
            'modele_id' => $request->modele_id,
            'immatriculation' => $request->immatriculation,
            'numero_chassis' => $request->numero_chassis,
            'kilometrage' => $request->kilometrage,
            'date_mise_en_service' => $request->date_mise_en_service,
        ]);

        return new PostResource(true, 'vehicule mis à jour avec succès', $vehicule);
    }

    // Supprimer un vehicule
    public function destroy(Vehicule $vehicule)
    {
        $vehicule->delete();
        return new PostResource(true, 'vehicule supprimé avec succès', null);
    }
}
