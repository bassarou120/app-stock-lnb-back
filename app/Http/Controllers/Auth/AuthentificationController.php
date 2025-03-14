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

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    //-------------------- fonction de register


    public function register(RegisterRequest $request)
    {
        $input = $request->all();

        $this->authService->register($input);

        // LogService::storeLogInfo("Inscrition de l'utilisateur" . $request->input("name"));

        // return $this->sendResponse([], 'Utilisateur enregistré');
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur enregistré',
        ], 403);
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
                ], 403);
            }
        }
    }


    
}
