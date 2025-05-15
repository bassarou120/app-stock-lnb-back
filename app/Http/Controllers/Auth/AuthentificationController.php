<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\LogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Http\Resources\PostResource;

class AuthentificationController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    //-------------------- fonction de register


    public function register(RegisterRequest $request)
    {
        $input = $request->all();

        try {
            // Appeler la fonction d'enregistrement dans AuthService
            $this->authService->register($input);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur enregistré avec succès!',
            ], 201); // Code HTTP 201 pour "created"
        } catch (\Exception $e) {
            // Si une exception est lancée (par exemple, rôle inexistant), renvoyer une erreur
            Log::error('Erreur lors de l\'inscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500); // Code HTTP 500 pour "Internal Server Error"
        }
    }






    //-------------------- fonction de login
    public function login(LoginRequest $request)
    {
        $input = $request->all();

        $result = $this->authService->login($input);

        if ($result[0]) {
            // LogService::storeLogInfo("Connexion");

            $user = $result[1]['user'];

            if ($user->active == 1) {
                return response()->json([
                    'success' => true,
                    'data' => $result[1],
                    'message' => 'Utilisateur authentifié avec succès! 😁'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte inactif!',
                    'errors' => ['failed' => 'Compte inactif. Veuillez vous rapprocher d\'un administrateur']
                ], 403);
            }
        } else {
            if ($result[1] == 'erreurs identifiants') {
                // LogService::storeLogInfo("Tentative de connexion");
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects!',
                    'errors' => ['failed' => 'Identifiants incorrects']
                ], 401); // Code HTTP 401 pour "Unauthorized"
            }else if ($result[1] == 'inactif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte inactif!',
                    'errors' => ['failed' => 'Votre compte est inactif. Veuillez contacter un administrateur.']
                ], 403); // Code HTTP 403 pour "Forbidden"
            }
        }
    }

    //-------------------- Fonction de déconnexion (logout)

    public function logout(Request $request)
    {
        try {
            // Récupérer le token de la requête
            $token = $request->bearerToken();

            // Si le token est présent, l'envoyer au service pour révoquer tous les tokens
            if ($token) {
                $result = $this->authService->logout($token);

                if ($result[0]) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Utilisateur déconnecté avec succès! 😁'
                    ], 200); // Code HTTP 200 pour "OK"
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result[1], // Message retourné par le service
                        'errors' => ['failed' => 'Erreur lors de la déconnexion']
                    ], 500); // Code HTTP 500 pour "Internal Server Error"
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun token trouvé pour la déconnexion.',
                    'errors' => ['failed' => 'Token non trouvé']
                ], 400); // Code HTTP 400 pour "Bad Request"
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la déconnexion.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500); // Code HTTP 500 pour "Internal Server Error"
        }
    }



}
