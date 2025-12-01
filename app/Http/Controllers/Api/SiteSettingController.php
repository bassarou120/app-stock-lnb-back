<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting; // Importe votre modèle Setting
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Pour gérer le stockage de fichiers
use Illuminate\Support\Facades\Validator; // Pour la validation des requêtes
use Illuminate\Support\Str; // Pour générer des noms de fichiers aléatoires
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class SiteSettingController extends Controller
{
    /**
     * Récupère tous les paramètres du site (nom de l'entreprise, logo).
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        try {
            // Récupère tous les paramètres de la table 'settings'
            $settings = Setting::all();
            return response()->json($settings, 200);
        } catch (\Exception $e) {
            LogJournalisation::create([
                'action'     => "Échec: Tentative de consultation des paramètres de configuration du site (Erreur système).",
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            // Log l'erreur pour le débogage (dans storage/logs/laravel.log)
            \Log::error('Erreur lors de la récupération des paramètres du site: ' . $e->getMessage(), ['exception' => $e]);
            // Retourne une réponse d'erreur générique
            return response()->json(['message' => 'Erreur lors du chargement des paramètres du site.'], 500);
        }
    }

    /**
     * Met à jour un paramètre existant ou en crée un nouveau (nom de l'entreprise, logo).
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        // Log la requête reçue pour le débogage (Déjà présent, peut être conservé)
        \Log::info('Requête API /site-settings/store reçue:', $request->all());

        // Valide les données de la requête
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255', // La clé du paramètre (ex: 'company_name', 'logo_url')
            'value' => 'nullable|string', // La valeur (texte ou chaîne Base64 pour le logo)
            'type' => 'nullable|string|max:255', // Le type (ex: 'text', 'image_url', 'base64_image')
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation échouée pour /site-settings/store:', $validator->errors()->toArray());
            
            // ❌ LOG → Échec de validation
            LogJournalisation::create([
                'action'     => "Échec: Tentative de modification des paramètres (Validation échouée)",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $key = $data['key'];
        $value = $data['value'];
        $type = $data['type'] ?? 'text';
        $operation = 'Mise à jour'; // Supposons une mise à jour par défaut

        // Tente de trouver le paramètre existant par sa clé
        $currentSetting = Setting::where('key', $key)->first();
        if (!$currentSetting) {
            $operation = 'Création'; // Si non trouvé, ce sera une création
        }
        
        $valueToStore = $value;
        $logDetails = ""; // Détails supplémentaires pour le log

        try {
            // Gère spécifiquement l'upload de logo en Base64
            if ($key === 'logo_url' && $type === 'base64_image') {
                
                if (empty($value)) {
                    // Suppression du logo
                    if ($currentSetting && $currentSetting->value && Storage::disk('public')->exists($currentSetting->value)) {
                        Storage::disk('public')->delete($currentSetting->value); // Supprime l'ancien fichier
                        $logDetails = " (Ancien logo supprimé).";
                    }
                    $valueToStore = null;
                    $type = 'image_url';
                    $logDetails .= " (Valeur: null/Logo supprimé)";

                } else {
                    // Nouveau logo Base64
                    // ... (logique de décodage et d'enregistrement du Base64) ...
                    list($mimeType, $base64Data) = explode(';', $value);
                    list(, $base64Data) = explode(',', $base64Data);

                    $decodedImage = base64_decode($base64Data);
                    if ($decodedImage === false) {
                        throw new \Exception('Failed to decode base64 image data.');
                    }

                    $extension = explode('/', explode(':', $mimeType)[1])[1];
                    if ($extension === 'jpeg') $extension = 'jpg';

                    $fileName = 'logo_' . Str::random(10) . '.' . $extension;
                    $path = 'logos/' . $fileName; 

                    // Supprime l'ancien logo si un nouveau est téléchargé
                    if ($currentSetting && $currentSetting->value && Storage::disk('public')->exists($currentSetting->value)) {
                        Storage::disk('public')->delete($currentSetting->value);
                        $logDetails = " (Ancien logo supprimé et remplacé).";
                    }

                    // Stocke le nouveau fichier image
                    Storage::disk('public')->put($path, $decodedImage);
                    $valueToStore = $path; // La valeur à stocker en DB est le chemin relatif
                    $type = 'image_url';
                    $logDetails .= " (Nouveau logo enregistré: {$valueToStore})";
                }
            } else {
                // Pour les paramètres non-logo ou logo via URL directe
                $valueToStore = $value;
                $logDetails = " (Nouvelle valeur: " . Str::limit($valueToStore, 50) . ")";
            }

            // Crée ou met à jour le paramètre dans la base de données
            $setting = Setting::updateOrCreate(
                ['key' => $key], // Recherche par la clé
                ['value' => $valueToStore, 'type' => $type] // Données à mettre à jour/créer
            );

            // Préparation de la valeur de retour (URL complète si c'est le logo)
            $responseValue = $valueToStore;
            if ($key === 'logo_url' && !empty($valueToStore)) {
                $responseValue = asset('storage/' . $valueToStore);
            }

            // ✅ LOG → Succès de l'opération
            LogJournalisation::create([
                'action'     => "{$operation} réussie du paramètre de site: '{$key}'.{$logDetails}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            
            // Retourne une structure de réponse claire
            return response()->json([
                'success' => true,
                'message' => "Paramètre '{$key}' {$operation} avec succès.",
                'data' => [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => $responseValue,
                    'type' => $setting->type
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du traitement du paramètre ' . $key . ': ' . $e->getMessage(), ['exception' => $e]);
            
            // ❌ LOG → Échec d'exécution
            LogJournalisation::create([
                'action'     => "Échec critique: {$operation} du paramètre '{$key}'. Erreur: {$e->getMessage()}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            
            return response()->json(['message' => 'Erreur serveur lors de la mise à jour des paramètres.'], 500);
        }
    }



    public function getThemeColors(): \Illuminate\Http\JsonResponse
    {
        try {
            // Récupère les paramètres de couleur
            $colorSettings = Setting::whereIn('key', [
                'primary_color',
                'secondary_color',
                'success_color',
                'danger_color',
                'warning_color',
                'info_color',
                'dark_color',
                'light_color'
            ])->get();

            // Formate les couleurs en objet
            $colors = [];
            foreach ($colorSettings as $setting) {
                $colors[$setting->key] = $setting->value;
            }

            // Valeurs par défaut si aucune couleur n'est définie
            $defaultColors = [
                'primary_color' => '#4d8af0',
                'secondary_color' => '#6c757d',
                'success_color' => '#28a745',
                'danger_color' => '#dc3545',
                'warning_color' => '#ffc107',
                'info_color' => '#17a2b8',
                'dark_color' => '#343a40',
                'light_color' => '#f8f9fa'
            ];

            // Merge avec les valeurs par défaut
            $finalColors = array_merge($defaultColors, $colors);

            return response()->json($finalColors, 200);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des couleurs du thème: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors du chargement des couleurs du thème.'], 500);
        }
    }

}
