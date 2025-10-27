<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


use App\Models\Parametrage\Marque;
use App\Models\Parametrage\Modele;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VehiculeController extends Controller
{
     // Afficher la liste des véhicules
    public function index()
     {
         $vehicules = Vehicule::with(['modele', 'marque', 'sousTypeImmo', 'groupeTypeImmo'])
         ->where('isdeleted', false)
         ->latest()->paginate(1000);

         return new PostResource(true, 'Liste des véhicules', $vehicules);
     } 

     public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicules' => 'required|array',
            'vehicules.*.marque_id' => 'required|exists:marques,id',
            'vehicules.*.modele_id' => 'required|exists:modeles,id',
            'vehicules.*.immatriculation' => 'required|string|max:255',
            'vehicules.*.numero_chassis' => 'nullable|string|max:255',
            'vehicules.*.kilometrage' => 'required|integer',
            'vehicules.*.date_mise_en_service' => 'required',
            'vehicules.*.puissance' => 'nullable|string|max:100',
            'vehicules.*.places_assises' => 'nullable|integer',
            'vehicules.*.energie' => 'nullable|string|max:50',
            'vehicules.*.date_amortissement' => 'required',
            'vehicules.*.nbreannee_amortissement' => 'required|integer',
            'vehicules.*.id_sous_type_immo' => 'required|exists:sous_type_immos,id',
            'vehicules.*.id_groupe_type_immo' => 'required|exists:groupe_type_immos,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $vehicules = [];

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            foreach ($request->vehicules as $vehiculeData) {
                //dd($vehiculeData);
                $vehicule = Vehicule::create([
                    'marque_id' => $vehiculeData['marque_id'],
                    'modele_id' => $vehiculeData['modele_id'],
                    'immatriculation' => $vehiculeData['immatriculation'],
                    'numero_chassis' => $vehiculeData['numero_chassis'] ?? null,
                    'kilometrage' => $vehiculeData['kilometrage'],
                    'date_mise_en_service' => $vehiculeData['date_mise_en_service'],
                    'puissance' => $vehiculeData['puissance'] ?? null,
                    'places_assises' => $vehiculeData['places_assises'] ?? null,
                    'energie' => $vehiculeData['energie'] ?? null,
                    'date_amortissement' => $vehiculeData['date_amortissement'],
                    'nbreannee_amortissement' => $vehiculeData['nbreannee_amortissement'],
                    'id_sous_type_immo' => $vehiculeData['id_sous_type_immo'],
                    'id_groupe_type_immo' => $vehiculeData['id_groupe_type_immo'],
                ]);



                $vehicules[] = $vehicule;
            }
            DB::commit();
        } catch (\Illuminate\Database\QueryException $qe) {
        DB::rollBack();
        // Affiche l'erreur SQL exacte
        return response()->json([
            'status' => 'query_error',
            'message' => $qe->getMessage(),
            'sql' => $qe->getSql(),
            'bindings' => $qe->getBindings()
        ], 500);

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
            'numero_chassis' => 'nullable|string|max:255',
            'kilometrage' => 'required|integer',
            'date_mise_en_service' => 'required|date',
            'puissance' => 'nullable|string|max:100',
            'places_assises' => 'nullable|integer',
            'energie' => 'nullable|string|max:50',
            'date_amortissement' => 'nullable|date',
            'nbreannee_amortissement' => 'nullable|integer',
            'id_sous_type_immo' => 'required|exists:sous_type_immos,id',
            'id_groupe_type_immo' => 'required|exists:groupe_type_immos,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Gérer l'upload de la nouvelle carte grise
        $data = $request->except(['_method']);
        if ($request->hasFile('carte_grise')) {
            // Supprimer l'ancien fichier s'il existe
            if ($vehicule->carte_grise && Storage::disk('public')->exists($vehicule->carte_grise)) {
                Storage::disk('public')->delete($vehicule->carte_grise);
            }

            // Stocker le nouveau fichier
            $path = $request->file('carte_grise')->store('cartes-grises', 'public');
            $data['carte_grise'] = $path;
        }

        $vehicule->update($data);

        return new PostResource(true, 'vehicule mis à jour avec succès', $vehicule);
    }

    // Supprimer un vehicule
    public function destroy(Vehicule $vehicule)
    {
        // Supprimer le fichier de la carte grise associé avant de supprimer l'enregistrement
        if ($vehicule->carte_grise && Storage::disk('public')->exists($vehicule->carte_grise)) {
            Storage::disk('public')->delete($vehicule->carte_grise);
        }

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

    /* public function import(Request $request)
    {
        // 1️⃣ Validation
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2️⃣ Initialisation des compteurs et du tableau de rapport
        $totalRows = 0;
        $successCount = 0;
        $ignoredRows = []; // Tableau pour stocker les messages des lignes ignorées

        try {
            //code...
            // 2️⃣ Charger le fichier
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $nbIgnored = 0; // Compter les véhicules ignorés

            // 3️⃣ Boucler sur les lignes (en ignorant la première ligne d'entêtes)
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Ignore header

                $totalRows++; // Compter le nombre total de lignes traitées (hors entête)
        
                $immatriculation = $row[0];
                $numero_chassis = $row[1];
                $kilometrage = $row[2];
                $date_mise_en_service = $row[3];
                $marqueNom = $row[4];
                $modeleNom = $row[5];
                $puissance = $row[6];
                $places_assises = $row[7];
                $energie = $row[8];

                // 🛡️ Vérification des données essentielles (CORRIGÉE : utilisation de $immatriculation et $marqueNom)
                if (empty($immatriculation) || empty($marqueNom)) {
                    $msg = "Ligne " . ($index + 1) . " ignorée : Immatriculation ou Marque manquante.";
                    $ignoredRows[] = $msg;
                    \Log::warning($msg);
                    continue;
                }

                // 🔎 Vérification doublons (CORRIGÉE : utilisation des variables $immatriculation et $numero_chassis)
                // REMARQUE: Vous avez deux blocs de vérification de doublons. Je recommande de n'en garder qu'un,
                // celui qui vérifie par immatriculation OU chassis (plus robuste). Je retire le bloc "plaque".

                // Ligne ignorée: $vehiculeExistant = Vehicule::where('plaque', $plaque)->first();

                // 🔎 Vérification de doublons par Immatriculation OU Numéro de Châssis (le plus robuste)
                $vehiculeExiste = Vehicule::where('immatriculation', $immatriculation)
                    ->orWhere('numero_chassis', $numero_chassis)
                    ->exists();

                if ($vehiculeExiste) {
                    $msg = "Ligne " . ($index + 1) . " ignorée : Véhicule avec immatriculation '$immatriculation' ou chassis '$numero_chassis' existe déjà.";
                    $ignoredRows[] = $msg;
                    \Log::info($msg);
                    // $nbIgnored++; // Variable inutile, on compte via $ignoredRows
                    continue; // Ignore cette ligne si déjà existante
                }
        
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
                ]); 
                $successCount++;
            }

            $summary = "Importation terminée. " . $successCount . " ligne(s) ajoutée(s) sur " . $totalRows . " ligne(s) de données traitée(s).";
            
            if (!empty($ignoredRows)) {
                $summary .= " Attention : " . count($ignoredRows) . " ligne(s) ont été ignorée(s).";
            }

            $response = [
                'message' => $summary,
                'success_count' => $successCount,
                'total_rows_processed' => $totalRows,
                'ignored' => $ignoredRows
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            // En cas d'erreur de lecture de fichier ou autre
            return response()->json([
                'error' => 'Erreur lors de l\'importation du fichier.',
                'details' => $e->getMessage()
            ], 500);
        }

    } */

    public function import(Request $request)
{
    // 1️⃣ Validation (Inchangée)
    $validator = Validator::make($request->all(), [
        'file' => 'required|mimes:xlsx,xls',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // 2️⃣ Initialisation des compteurs et du tableau de rapport (Inchangée)
    $totalRows = 0;
    $successCount = 0;
    $ignoredRows = [];

    try {
        // 2️⃣ Charger le fichier (Inchangé)
        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // 3️⃣ Boucler sur les lignes (en ignorant la première ligne d'entêtes)
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Ignore header

            // 🛑 AJOUT CLÉ : Ignorer les lignes complètement vides 🛑
            // array_filter supprime les valeurs nulles, vides ou égales à 0 (sauf si '0' est le contenu).
            // Si le tableau filtré est vide, c'est que la ligne entière est vide ou contient des espaces.
            if (empty(array_filter($row, function($value) {
                // Considérez la ligne vide si toutes les valeurs sont nulles ou des chaînes vides après avoir enlevé les espaces
                return !is_null($value) && trim($value) !== ''; 
            }))) {
                continue; // Passe à la ligne suivante (ignore cette ligne vide)
            }
            // ----------------------------------------------------

            $totalRows++; // Compter le nombre total de LIGNES DE DONNÉES réelles traitées.
    
            $immatriculation = $row[0];
            $numero_chassis = $row[1];
            // ... (Définition des autres variables inchangée) ...
            $kilometrage = $row[2];
            $date_mise_en_service = $row[3];
            $marqueNom = $row[4];
            $modeleNom = $row[5];
            $puissance = $row[6];
            $places_assises = $row[7];
            $energie = $row[8];

            // 🛡️ La vérification des données essentielles reste très importante
            if (empty($immatriculation) || empty($marqueNom)) {
                $msg = "Ligne " . ($index + 1) . " ignorée : Immatriculation ou Marque manquante. (Ligne de données non complètement vide)";
                $ignoredRows[] = $msg;
                \Log::warning($msg);
                continue;
            }
            
            // 🔎 Vérification de doublons (Inchangée)
            $vehiculeExiste = Vehicule::where('immatriculation', $immatriculation)
                ->orWhere('numero_chassis', $numero_chassis)
                ->exists();

            if ($vehiculeExiste) {
                $msg = "Ligne " . ($index + 1) . " ignorée : Véhicule avec immatriculation '$immatriculation' ou chassis '$numero_chassis' existe déjà.";
                $ignoredRows[] = $msg;
                \Log::info($msg);
                continue;
            }
            
            // 🔎 Trouver les IDs correspondants (Inchangée)
            $marque = Marque::firstOrCreate(['libelle' => $marqueNom]);
            $modele = Modele::firstOrCreate([
                'libelle_modele' => $modeleNom,
            ]);
            
            // 🚗 Créer le véhicule (Inchangée)
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
            ]); 
            $successCount++;
        }

        // 4️⃣ Retourner la réponse (Inchangée)
        $summary = "Importation terminée. " . $successCount . " ligne(s) ajoutée(s) sur " . $totalRows . " ligne(s) de données traitée(s).";
        
        if (!empty($ignoredRows)) {
            $summary .= " Attention : " . count($ignoredRows) . " ligne(s) ont été ignorée(s).";
        }

        $response = [
            'message' => $summary,
            'success_count' => $successCount,
            'total_rows_processed' => $totalRows,
            'ignored' => $ignoredRows
        ];

        return response()->json($response);

    } catch (\Exception $e) {
        // En cas d'erreur de lecture de fichier ou autre
        return response()->json([
            'error' => 'Erreur lors de l\'importation du fichier.',
            'details' => $e->getMessage()
        ], 500);
    }
}


    public function addCarteGrise(Request $request, Vehicule $vehicule)
    {
        $validator = Validator::make($request->all(), [
            'carte_grise' => 'required|file|mimes:pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Upload du fichier
        if ($request->hasFile('carte_grise')) {
            $file = $request->file('carte_grise');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('carte_grises', $filename, 'public');

            // Mise à jour du véhicule avec le chemin du fichier
            $vehicule->update([
                'carte_grise' => 'storage/'.$path,
            ]);
        }

        return new PostResource(true, 'Carte grise ajoutée avec succès', $vehicule);
    }


}