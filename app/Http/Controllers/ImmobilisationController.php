<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\Fournisseur;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use App\Models\Parametrage\TypeImmo;
use App\Models\Parametrage\StatusImmo;
use App\Models\Vehicule;
use App\Models\Transfert;
use App\Models\LogJournalisation; // Ajout du modèle de journalisation
use Illuminate\Support\Facades\Auth; // Ajout pour récupérer l'ID utilisateur
use PhpOffice\PhpSpreadsheet\IOFactory;


class ImmobilisationController extends Controller
{
    // ... [index] inchangé
    public function index()
    {
        $immos = Immobilisation::with([
            'vehicule',
            'groupeTypeImmo',
            'sousTypeImmo',
            'statusImmo',
            'employe',
            'bureau',
            'fournisseur'
        ])
            ->where('isdeleted', false)
            ->whereHas('statusImmo', function ($query) {
                $query->where('libelle_status_immo', '!=', 'Sortie de patrimoine');
            })
            ->latest()
            ->paginate(100);

        return new PostResource(true, 'Liste des immobilisations', $immos);
    }
    // ...

    // Créer une nouvelle immobilisation
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bureau_id' => 'nullable|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'date_mouvement' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'designation' => 'nullable|string|max:255',
            'isVehicule' => 'boolean',
            'vehicule_id' => 'nullable|exists:vehicules,id',
            'code' => 'nullable|string|max:255',
            'id_groupe_type_immo' => 'required|exists:groupe_type_immos,id',
            'id_sous_type_immo' => 'required|exists:sous_type_immos,id',
            'duree_amorti' => 'nullable|integer',
            'etat' => 'nullable|string',
            'taux_ammortissement' => 'nullable|integer',
            'duree_ammortissement' => 'nullable|integer',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'observation' => 'nullable|string',
            'id_status_immo' => 'required|exists:status_immos,id',
            'montant_ttc' => 'nullable|integer',
            'reference_estampillonnage' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            // 📝 LOG → Échec de validation (création)
            LogJournalisation::create([
                'action'     => 'Échec de validation (création immobilisation)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => json_encode($validator->errors())
            ]);
            return response()->json($validator->errors(), 422);
        }

        // Démarre une transaction de base de données
        DB::beginTransaction();

        try {
            // Création de l'immobilisation
            $immo = Immobilisation::create($request->all());
            //$immo = Immobilisation::create($immoData);

            // Crée un enregistrement de transfert si le bureau ou l'employé est renseigné
            if ($request->filled('bureau_id') || $request->filled('employe_id')) {
                Transfert::create([
                    'immo_id' => $immo->id, // On lie l'immobilisation au transfert
                    'old_bureau_id' => null, // Ancien bureau est "Magasin", donc null
                    'old_employe_id' => null, // Ancien employé est null
                    'bureau_id' => $request->get('bureau_id'),
                    'employe_id' => $request->get('employe_id'),
                    'date_mouvement' => now(), // Date du jour
                    'observation' => $request->observation,
                ]);
            }

            // Met à jour la date de mise en service de l'immobilisation
            $immo->date_mise_en_service = $immo->date_acquisition;
            $immo->save();

            // Si tout s'est bien passé, on valide la transaction
            DB::commit();

            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création immobilisation réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Immo ID: {$immo->id}, Code: {$immo->code}"
            ]);

            return new PostResource(true, 'Immobilisation créée avec succès', $immo);

        } catch (\Exception $e) {
            // En cas d'erreur, on annule la transaction
            DB::rollBack();

            // 📝 LOG → Création échouée (exception)
            LogJournalisation::create([
                'action'     => 'Création immobilisation échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $e->getMessage()
            ]);

            // Log l'erreur pour le débogage et retourne un message d'erreur
            \Log::error('Erreur lors de la création de l\'immobilisation et de son transfert : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'immobilisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mettre à jour une immobilisation existante
    public function update(Request $request, Immobilisation $immobilisation)
    {
        $validator = Validator::make($request->all(), [
            'bureau_id' => 'nullable|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'date_mouvement' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'designation' => 'nullable|string|max:255',
            'isVehicule' => 'boolean',
            'vehicule_id' => 'nullable|exists:vehicules,id',
            'code' => 'nullable|string|max:255',
            'id_groupe_type_immo' => 'required|exists:groupe_type_immos,id',
            'id_sous_type_immo' => 'required|exists:sous_type_immos,id',
            'duree_amorti' => 'nullable|integer',
            'etat' => 'nullable|string',
            'taux_ammortissement' => 'nullable|integer',
            'duree_ammortissement' => 'nullable|integer',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'observation' => 'nullable|string',
            'id_status_immo' => 'required|exists:status_immos,id',
            'montant_ttc' => 'nullable|integer',
            'reference_estampillonnage' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            // 📝 LOG → Échec de validation (mise à jour)
            LogJournalisation::create([
                'action'     => 'Échec de validation (mise à jour immobilisation)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Immo ID: {$immobilisation->id}, Erreurs: " . json_encode($validator->errors())
            ]);
            return response()->json($validator->errors(), 422);
        }

        try {
            $immobilisation->update($request->all());

            // 📝 LOG → Mise à jour réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour immobilisation réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Immo ID: {$immobilisation->id}, Code: {$immobilisation->code}"
            ]);

            return new PostResource(true, 'Immobilisation mise à jour avec succès', $immobilisation);
        } catch (\Exception $e) {
            // 📝 LOG → Mise à jour échouée (exception)
            LogJournalisation::create([
                'action'     => 'Mise à jour immobilisation échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Immo ID: {$immobilisation->id}, Erreur: " . $e->getMessage()
            ]);
            \Log::error('Erreur lors de la mise à jour de l\'immobilisation : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'immobilisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une immobilisation
    public function destroy(Immobilisation $immobilisation, Request $request) // Ajout de Request pour obtenir l'IP/User-Agent
    {
        try {
            $immobilisation->isdeleted = true;
            $immobilisation->save();

            // 📝 LOG → Suppression réussie
            LogJournalisation::create([
                'action'     => 'Suppression immobilisation réussie (soft delete)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Immo ID: {$immobilisation->id}, Code: {$immobilisation->code}"
            ]);

            return new PostResource(true, 'Immobilisation supprimée avec succès', null);
        } catch (\Exception $e) {
            // 📝 LOG → Suppression échouée (exception)
            LogJournalisation::create([
                'action'     => 'Suppression immobilisation échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Immo ID: {$immobilisation->id}, Erreur: " . $e->getMessage()
            ]);
            \Log::error('Erreur lors de la suppression de l\'immobilisation : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'immobilisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ... [imprimerImmos] inchangé
    public function imprimerImmos()
    {
        // Récupère toutes les immobilisations avec leurs relations nécessaires
        $immobilisations = Immobilisation::with([
            'vehicule',
            'groupeTypeImmo',
            'sousTypeImmo',
            'statusImmo',
            'employe',
            'bureau',
            'fournisseur'
        ])
            ->where('isdeleted', false)
            ->latest()
            ->get();

        $pdf = \Pdf::loadView('pdf.immobilisations', compact('immobilisations'));

        return $pdf->download('liste_immobilisations.pdf');
    }
    // ...

    public function import(Request $request)
    {
        // 1️⃣ Validation du fichier
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            // 📝 LOG → Échec de validation (import)
            LogJournalisation::create([
                'action'     => 'Échec de validation (import immobilisations)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => json_encode($validator->errors())
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 2️⃣ Chargement du fichier
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $ignoredRows = [];
            $importedCount = 0;
            $realLine = 1; // correspond à la ligne Excel réelle (pour les logs)

            // 3️⃣ Boucle sur les lignes (en ignorant la première ligne d'entête)
            foreach ($rows as $index => $row) {
                $realLine++;

                if ($index === 0) continue; // sauter l'entête

                // 🔍 Vérifie si la ligne est entièrement vide
                $isEmpty = true;
                foreach ($row as $cell) {
                    if (trim((string)$cell) !== '') {
                        $isEmpty = false;
                        break;
                    }
                }

                if ($isEmpty) {
                    continue; // Ignore totalement la ligne vide
                }

                // 🛡️ Vérifie le nombre de colonnes
                if (count($row) < 22) {
                    $msg = "Ligne $realLine ignorée : colonnes insuffisantes (" . count($row) . ")";
                    \Log::warning($msg);
                    $ignoredRows[] = $msg;
                    // 📝 LOG → Ligne ignorée (colonnes)
                    LogJournalisation::create([
                        'action'     => 'Import - Ligne ignorée (colonnes insuffisantes)',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'user_id'    => Auth::id(),
                        'date_action'=> now(),
                        'details'    => $msg
                    ]);
                    continue;
                }

                // --- Extraction propre (inchangée) ---
                [
                    $bureau,
                    $employe_fullname,
                    $date_mouvement,
                    $fournisseur,
                    $compte,
                    $type_immo,
                    $designation,
                    $isVehicule,
                    $vehicule,
                    $code,
                    $groupe_type_immo,
                    $sous_type_immo,
                    $duree_amorti,
                    $etat,
                    $taux_ammortissement,
                    $duree_ammortissement,
                    $date_acquisition,
                    $date_mise_en_service,
                    $observation,
                    $status_immo,
                    $montant_ttc,
                    $reference_estampillonnage
                ] = array_map(fn($v) => trim((string)$v), $row);

                // 🔎 Vérif doublon (isdeleted = false)
                $immobilisationExistante = Immobilisation::where(function ($query) use ($code, $designation) {
                    $query->where('code', $code)
                        ->orWhere('designation', $designation);
                })
                    ->where('isdeleted', false)
                    ->first();

                if ($immobilisationExistante) {
                    $msg = "Ligne $realLine ignorée : immobilisation avec code '$code' ou désignation '$designation' déjà active.";
                    \Log::info($msg);
                    $ignoredRows[] = $msg;
                    // 📝 LOG → Ligne ignorée (doublon)
                    LogJournalisation::create([
                        'action'     => 'Import - Ligne ignorée (doublon)',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'user_id'    => Auth::id(),
                        'date_action'=> now(),
                        'details'    => $msg
                    ]);
                    continue;
                }

                // --- Relations liées (inchangée) ---
                $bureau_id = Bureau::firstOrCreate(['libelle_bureau' => $bureau]);
                $fournisseur_id = Fournisseur::firstOrCreate(['nom' => $fournisseur]);
                $type_immo_id = TypeImmo::firstOrCreate(['libelle_typeImmo' => $type_immo, 'compte' => $compte])->id;

                if (empty($groupe_type_immo)) {
                    $msg = "Ligne $realLine ignorée : groupe type immo vide.";
                    \Log::warning($msg);
                    $ignoredRows[] = $msg;
                    // 📝 LOG → Ligne ignorée (groupe vide)
                    LogJournalisation::create([
                        'action'     => 'Import - Ligne ignorée (groupe vide)',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'user_id'    => Auth::id(),
                        'date_action'=> now(),
                        'details'    => $msg
                    ]);
                    continue;
                }

                $id_groupe_type_immo = GroupeTypeImmo::firstOrCreate([
                    'libelle' => $groupe_type_immo,
                    'compte' => $compte
                ]);

                $id_sous_type_immo = SousTypeImmo::firstOrCreate([
                    'libelle' => $sous_type_immo,
                    'compte' => $compte,
                    'id_type_immo' => $type_immo_id
                ]);

                $id_status_immo = StatusImmo::firstOrCreate(['libelle_status_immo' => $status_immo]);

                // 👤 Employé (inchangé)
                $employe = null;
                if (!empty($employe_fullname)) {
                    $parts = preg_split('/\s+/', trim($employe_fullname));
                    $nom = array_shift($parts);
                    $prenom = implode(' ', $parts);
                    if (!empty($nom) && !empty($prenom)) {
                        $employe = Employe::firstOrCreate(['nom' => $nom, 'prenom' => $prenom]);
                    }
                }

                // 🗓️ Formats de dates automatiques (inchangé)
                $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y'];
                $formatDate = fn($date) => collect($formats)
                    ->map(fn($fmt) => \DateTime::createFromFormat($fmt, $date))
                    ->filter()
                    ->first()?->format('Y-m-d');

                $date_mouvement_formatee = $formatDate($date_mouvement);
                $date_acquisition_formatee = $formatDate($date_acquisition);
                $date_mise_en_service_formatee = $formatDate($date_mise_en_service);

                if (!$date_mouvement_formatee || !$date_acquisition_formatee || !$date_mise_en_service_formatee) {
                    $msg = "Ligne $realLine ignorée : une ou plusieurs dates invalides.";
                    \Log::warning($msg);
                    $ignoredRows[] = $msg;
                    // 📝 LOG → Ligne ignorée (date invalide)
                    LogJournalisation::create([
                        'action'     => 'Import - Ligne ignorée (date invalide)',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'user_id'    => Auth::id(),
                        'date_action'=> now(),
                        'details'    => $msg . " Dates: $date_mouvement, $date_acquisition, $date_mise_en_service"
                    ]);
                    continue;
                }

                // ✅ Insertion (inchangée)
                Immobilisation::create([
                    'bureau_id' => $bureau_id->id,
                    'employe_id' => $employe?->id,
                    'date_mouvement' => $date_mouvement_formatee,
                    'fournisseur_id' => $fournisseur_id->id,
                    'designation' => $designation,
                    'isVehicule' => false,
                    'vehicule_id' => null,
                    'code' => $code,
                    'id_groupe_type_immo' => $id_groupe_type_immo->id,
                    'id_sous_type_immo' => $id_sous_type_immo->id,
                    'duree_amorti' => $duree_amorti,
                    'etat' => $etat,
                    'taux_ammortissement' => $taux_ammortissement,
                    'duree_ammortissement' => round($duree_ammortissement),
                    'date_acquisition' => $date_acquisition_formatee,
                    'date_mise_en_service' => $date_mise_en_service_formatee,
                    'observation' => $observation,
                    'id_status_immo' => $id_status_immo->id,
                    'montant_ttc' => $montant_ttc,
                    'reference_estampillonnage' => $reference_estampillonnage,
                    'isdeleted' => false,
                ]);

                $importedCount++;
            }

            // 📝 LOG → Import réussi (synthèse)
            LogJournalisation::create([
                'action'     => 'Import immobilisations terminé',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Importé: $importedCount, Ignoré: " . count($ignoredRows)
            ]);


            return response()->json([
                'message' => "Import terminé ! ($importedCount lignes importées)",
                'ignored' => $ignoredRows
            ]);

        } catch (\Exception $e) {
            // 📝 LOG → Import échoué (exception globale)
            LogJournalisation::create([
                'action'     => 'Import immobilisations échoué (exception globale)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $e->getMessage()
            ]);
            \Log::error('Erreur globale lors de l\'import : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur globale lors de l\'importation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ... [getCodesImmoEtVehicule] inchangé
    public function getCodesImmoEtVehicule(Request $request)
    {
        try {
            // Récupérer les codes des immobilisations (select 'id' et 'code')
            $codesImmo = Immobilisation::select('id', 'code')->get();

            // Récupérer les codes des véhicules (select 'id' et 'code', où 'isdeleted' est false)
            $codesVehicule = Vehicule::select('id', 'code')->where('isdeleted', false)->get();

            // Fusionner les deux collections en une seule
            // La collection $codesImmo recevra tous les éléments de $codesVehicule.
            $codesCombinés = $codesImmo->merge($codesVehicule);

            // Optionnel : Trier par code (si nécessaire)
            // $codesCombinés = $codesCombinés->sortBy('code')->values();

            // Renvoyer la collection combinée directement dans la clé 'data'
            // Vous pouvez choisir un nom de clé plus générique si vous le souhaitez, comme 'codes'
            $result = [
                'codes' => $codesCombinés
            ];

            // 📝 LOG → Récupération codes réussie
            // Ce type de log peut être omis s'il est trop verbeux, mais je le mets pour l'exemple.
            LogJournalisation::create([
                'action'     => 'Récupération codes Immo/Véhicule réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Nombre de codes: " . count($codesCombinés)
            ]);

            return new PostResource(true, 'Liste combinée des codes récupérée avec succès.', $result);

        } catch (\Exception $e) {
            // 📝 LOG → Récupération codes échouée (exception)
            LogJournalisation::create([
                'action'     => 'Récupération codes Immo/Véhicule échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $e->getMessage()
            ]);

            return new PostResource(false, 'Erreur lors de la récupération des codes : ' . $e->getMessage());
        }
    }

    // ... [getDesignationByCode] inchangé
    public function getDesignationByCode($code, Request $request) // Ajout de Request pour la journalisation
    {

        $cleanCode = $code;
        //dd("cest bon", $cleanCode);

        // 1. Recherche dans la table des immobilisations
        // CLÉ : On utilise DB::raw('UPPER(code)') pour s'assurer que la colonne est comparée en majuscules
        $immobilisation = Immobilisation::where('code', $cleanCode) 
        ->select('id', 'code', DB::raw("designation AS designation_complete"), DB::raw("'Immobilisation' as type"))
        ->first();

        if ($immobilisation) {
            // 📝 LOG → Désignation trouvée (Immobilisation)
            LogJournalisation::create([
                'action'     => 'Recherche désignation réussie (Immo)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Code recherché: $code. Trouvé: Immobilisation ID {$immobilisation->id}"
            ]);

            // Retourne la donnée dans le format uniforme attendu par Angular
            return response()->json([
                'success' => true,
                'message' => 'Immobilisation trouvée.',
                'data' => [
                    'id' => $immobilisation->id,
                    'code' => $immobilisation->code,
                    'designation_complete' => $immobilisation->designation_complete, 
                    'type' => $immobilisation->type,
                ]
            ]);
        }

        // 2. Recherche dans la table des véhicules (inchangée)
        $vehicule = Vehicule::where('code', $cleanCode)
            ->with(['marque', 'modele']) 
            ->select('id', 'code', 'marque_id', 'modele_id', DB::raw("'Vehicule' as type"))
            ->first();

        if ($vehicule) {
            
            $marque = $vehicule->marque ? $vehicule->marque->libelle : 'Marque Inconnue'; 
            $modele = $vehicule->modele ? $vehicule->modele->libelle_modele : 'Modèle Inconnu'; 
            
            $designation = trim($marque . ' - ' . $modele);
            
            // 📝 LOG → Désignation trouvée (Véhicule)
            LogJournalisation::create([
                'action'     => 'Recherche désignation réussie (Véhicule)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Code recherché: $code. Trouvé: Véhicule ID {$vehicule->id}"
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Véhicule trouvé.',
                'data' => [
                    'id' => $vehicule->id,
                    'code' => $vehicule->code,
                    'designation_complete' => $designation,
                    'type' => $vehicule->type,
                ]
            ]);
        }

        // 3. Actif non trouvé
        // 📝 LOG → Désignation non trouvée
        LogJournalisation::create([
            'action'     => 'Recherche désignation échouée (non trouvé)',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
            'details'    => "Code recherché: $code. Résultat: Non trouvé."
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Aucun actif trouvé pour ce code.',
            'data' => null
        ], 404);
    }
}