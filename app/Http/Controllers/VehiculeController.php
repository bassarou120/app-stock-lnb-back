<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


use App\Models\Parametrage\Marque;
use App\Models\Parametrage\Modele;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VehiculeController extends Controller
{
     // Afficher la liste des véhicules
     public function index()
     {
         $vehicules = Vehicule::with(['modele', 'marque'])
         ->where('isdeleted', false)
         ->latest()->paginate(1000);
         return new PostResource(true, 'Liste des véhicules', $vehicules);
     }

    public function storeBatch(Request $request)
    {
        // Rendre nbreannee_amortissement facultatif
        $validator = Validator::make($request->all(), [
            'vehicules' => 'required|array',
            'vehicules.*.marque_id' => 'required|exists:marques,id',
            'vehicules.*.modele_id' => 'required|exists:modeles,id',
            'vehicules.*.immatriculation' => 'required|string|max:255',
            'vehicules.*.numero_chassis' => 'nullable|string|max:255',
            'vehicules.*.kilometrage' => 'required|integer',
            'vehicules.*.date_mise_en_service' => 'required|date',
            'vehicules.*.puissance' => 'nullable|string|max:100',
            'vehicules.*.places_assises' => 'nullable|integer',
            'vehicules.*.energie' => 'nullable|string|max:50',
            'vehicules.*.nbreannee_amortissement' => 'nullable|integer|min:1', 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $vehicules = [];

        // Ne calculer la date d'amortissement que si le nombre d'années est fourni
        $vehiculeDataWithAmortissement = collect($request->vehicules)->map(function ($vehicule) {
            $vehicule['date_amortissement'] = null; // Initialiser à null

            // Assurer que la valeur est un entier avant d'effectuer le calcul
            if (isset($vehicule['nbreannee_amortissement']) && is_numeric($vehicule['nbreannee_amortissement'])) {
                // Utilisation de Carbon pour la manipulation des dates
                $dateMiseEnService = Carbon::parse($vehicule['date_mise_en_service']);
                $anneeAmortissement = (int) $vehicule['nbreannee_amortissement'];
                
                // Calcul de la date d'amortissement
                $dateAmortissement = $dateMiseEnService->addYears($anneeAmortissement);

                // Ajout du nouveau champ à la collection
                $vehicule['date_amortissement'] = $dateAmortissement->toDateString();
            }

            return $vehicule;
        });

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            foreach ($vehiculeDataWithAmortissement as $vehiculeData) {
                $vehicule = Vehicule::create([
                    'marque_id' => (int) $vehiculeData['marque_id'],
                    'modele_id' => (int) $vehiculeData['modele_id'],
                    'immatriculation' => $vehiculeData['immatriculation'],
                    'numero_chassis' => $vehiculeData['numero_chassis'] ?? null,
                    'kilometrage' => (int) $vehiculeData['kilometrage'],
                    'date_mise_en_service' => $vehiculeData['date_mise_en_service'],
                    'puissance' => $vehiculeData['puissance'] ?? null,
                    'places_assises' => isset($vehiculeData['places_assises']) ? (int) $vehiculeData['places_assises'] : null,
                    'energie' => $vehiculeData['energie'] ?? null,
                    // Utilisation de l'opérateur de coalescence null pour éviter les erreurs
                    'nbreannee_amortissement' => isset($vehiculeData['nbreannee_amortissement']) ? (int) $vehiculeData['nbreannee_amortissement'] : null, 
                    'date_amortissement' => $vehiculeData['date_amortissement'], 
                ]);

                $vehicules[] = $vehicule;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors de l\'enregistrement des véhicules.'], 500);
        }

        return new PostResource(true, count($vehicules) . ' véhicules créés avec succès', $vehicules);
    }


    // Mettre à jour un véhicule existant
public function update(Request $request, Vehicule $vehicule)
{
    $validator = Validator::make($request->all(), [
        'marque_id' => 'required|exists:marques,id',
        'modele_id' => 'required|exists:modeles,id',
        'immatriculation' => 'required|string|max:255',
        'numero_chassis' => 'nullable|string|max:255',
        'kilometrage' => 'required|integer',
        'date_mise_en_service' => 'required|date',
        'puissance' => 'nullable|string|max:100',
        'places_assises' => 'nullable|integer',
        'energie' => 'nullable|string|max:50',
        'vehicules.*.nbreannee_amortissement' => 'nullable|integer|min:1', 
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Récupérer les données de la requête
    $requestData = $request->all();

    // Calcul de la date d'amortissement
    $dateMiseEnService = \Carbon\Carbon::parse($requestData['date_mise_en_service']);
    $anneeAmortissement = $requestData['nbreannee_amortissement'];

    $dateAmortissement = $dateMiseEnService->addYears($anneeAmortissement)->toDateString();
    
    // Ajout de la date d'amortissement aux données de la requête
    $requestData['date_amortissement'] = $dateAmortissement;

    // Mise à jour du véhicule avec toutes les données, y compris la date calculée
    $vehicule->update($requestData);

    return new PostResource(true, 'véhicule mis à jour avec succès', $vehicule);
}

    // Supprimer un vehicule
    public function destroy(Vehicule $vehicule)
    {
        $vehicule->isdeleted = true;
        $vehicule->save();
        return new PostResource(true, 'vehicule supprimé avec succès', null);
    }

    // Méthode pour l'impression des mouvements d'entrée
    public function imprimerVehicules()
    {
        $vehicules = Vehicule::with(['modele', 'marque'])
                                    ->where('isdeleted', false)
                                    ->latest()
                                    ->get();


        $pdf = \Pdf::loadView('pdf.vehicule', compact('vehicules'));

        return $pdf->download('liste_vehicules.pdf');
    }

    public function import(Request $request)
    {
        // 1️⃣ Validation
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2️⃣ Charger le fichier
        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $nbIgnored = 0; // Compter les véhicules ignorés
        $nbCreated = 0; // Compter les véhicules créés

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();

        try {
            // 3️⃣ Boucler sur les lignes (en ignorant la première ligne d'entêtes)
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Ignore header

                // Récupérer les données de la ligne
                $immatriculation = $row[0];
                $numero_chassis = $row[1];
                $kilometrage = $row[2];
                $date_mise_en_service = $row[3];
                $marqueNom = $row[4];
                $modeleNom = $row[5];
                $puissance = $row[6];
                $places_assises = $row[7];
                $energie = $row[8];
                $nbreannee_amortissement = $row[9] ?? 5; // Utilise 5 si la valeur est nulle

                // 🔎 Trouver les IDs correspondants
                $marque = Marque::firstOrCreate(['libelle' => $marqueNom]);
                $modele = Modele::firstOrCreate([
                    'libelle_modele' => $modeleNom,
                ]);

                // ✅ Vérifier si la voiture existe déjà
                $vehiculeExiste = Vehicule::where('immatriculation', $immatriculation)
                    ->orWhere('numero_chassis', $numero_chassis)
                    ->exists();

                if ($vehiculeExiste) {
                    $nbIgnored++;
                    continue; // Ignore cette ligne si déjà existante
                }
                
                // Calcul de la date d'amortissement
                $dateMiseEnService = \Carbon\Carbon::createFromFormat('Y-m-d', $date_mise_en_service);
                $dateAmortissement = $dateMiseEnService->addYears($nbreannee_amortissement)->toDateString();

                // 🚗 Créer le véhicule
                Vehicule::create([
                    'immatriculation' => $immatriculation,
                    'numero_chassis' => $numero_chassis,
                    'kilometrage' => $kilometrage,
                    'date_mise_en_service' => $date_mise_en_service,
                    'puissance' => $puissance,
                    'places_assises' => $places_assises,
                    'energie' => $energie,
                    'marque_id' => $marque->id,
                    'modele_id' => $modele->id,
                    'nbreannee_amortissement' => $nbreannee_amortissement,
                    'date_amortissement' => $dateAmortissement, // Ajout du champ calculé
                ]);
                
                $nbCreated++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'importation.',
                'error_details' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Importation terminée avec succès.',
            'vehicules_crees' => $nbCreated,
            'vehicules_ignores' => $nbIgnored
        ]);
    }

}
