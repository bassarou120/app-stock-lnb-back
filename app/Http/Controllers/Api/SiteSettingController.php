<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting; // Importe votre modèle Setting
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Pour gérer le stockage de fichiers
use Illuminate\Support\Facades\Validator; // Pour la validation des requêtes
use Illuminate\Support\Str; // Pour générer des noms de fichiers aléatoires

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
        // Log la requête reçue pour le débogage
        \Log::info('Requête API /site-settings/store reçue:', $request->all());

        // Valide les données de la requête
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255', // La clé du paramètre (ex: 'company_name', 'logo_url')
            'value' => 'nullable|string', // La valeur (texte ou chaîne Base64 pour le logo)
            'type' => 'nullable|string|max:255', // Le type (ex: 'text', 'image_url', 'base64_image')
        ]);

        if ($validator->fails()) {
            // Log les erreurs de validation
            \Log::warning('Validation échouée pour /site-settings/store:', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422); // Code 422: Unprocessable Entity
        }

        $data = $validator->validated();
        $key = $data['key'];
        $value = $data['value'];
        $type = $data['type'] ?? 'text'; // Type par défaut 'text'

        // Tente de trouver le paramètre existant par sa clé
        $currentSetting = Setting::where('key', $key)->first();
        $valueToStore = $value; // Valeur par défaut à stocker

        try {
            // Gère spécifiquement l'upload de logo en Base64
            if ($key === 'logo_url' && $type === 'base64_image') {
                if (empty($value)) {
                    // Si la valeur est vide, cela signifie que le logo est supprimé
                    if ($currentSetting && $currentSetting->value && Storage::disk('public')->exists($currentSetting->value)) {
                        Storage::disk('public')->delete($currentSetting->value); // Supprime l'ancien fichier
                        \Log::info('Ancien logo supprimé: ' . $currentSetting->value);
                    }
                    $valueToStore = null; // Enregistre null en base de données
                    $type = 'image_url'; // Le type redevient 'image_url' même si la valeur est null
                } else {
                    // Décode l'image Base64
                    list($mimeType, $base64Data) = explode(';', $value);
                    list(, $base64Data) = explode(',', $base64Data);

                    $decodedImage = base64_decode($base64Data);
                    if ($decodedImage === false) {
                        throw new \Exception('Failed to decode base64 image data.');
                    }

                    // Détermine l'extension du fichier
                    $extension = explode('/', explode(':', $mimeType)[1])[1];
                    if ($extension === 'jpeg') $extension = 'jpg'; // Correction pour jpeg

                    // Génère un nom de fichier unique et le chemin de stockage
                    $fileName = 'logo_' . Str::random(10) . '.' . $extension;
                    $path = 'logos/' . $fileName; // Chemin relatif dans le dossier 'public' du stockage

                    // Supprime l'ancien logo si un nouveau est téléchargé
                    if ($currentSetting && $currentSetting->value && Storage::disk('public')->exists($currentSetting->value)) {
                        Storage::disk('public')->delete($currentSetting->value);
                        \Log::info('Ancien logo supprimé lors de la mise à jour: ' . $currentSetting->value);
                    }

                    // Stocke le nouveau fichier image
                    Storage::disk('public')->put($path, $decodedImage);
                    $valueToStore = $path; // La valeur à stocker en DB est le chemin relatif
                    $type = 'image_url'; // Le type enregistré est 'image_url' une fois stocké
                    \Log::info('Nouveau logo enregistré: ' . $path);
                }
            } else {
                // Pour les paramètres non-logo ou logo via URL directe
                $valueToStore = $value;
            }

            // Crée ou met à jour le paramètre dans la base de données
            $setting = Setting::updateOrCreate(
                ['key' => $key], // Recherche par la clé
                ['value' => $valueToStore, 'type' => $type] // Données à mettre à jour/créer
            );

            \Log::info("Paramètre '{$key}' mis à jour/créé avec succès. Valeur: {$valueToStore}");
            return response()->json($setting, 200); // Retourne le paramètre mis à jour

        } catch (\Exception $e) {
            \Log::error('Erreur lors du traitement du paramètre ' . $key . ': ' . $e->getMessage(), ['exception' => $e]);
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
