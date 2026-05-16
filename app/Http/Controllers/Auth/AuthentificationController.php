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
use App\Models\Exercice;
use App\Models\LogJournalisation;


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
            // 📝 LOG → Utilisateur inscrit
            LogJournalisation::create([
                'action'     => 'Inscription',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => null, // pas encore connecté
                'user_name'  => trim(($input['name'] ?? '') . ' ' . ($input['surname'] ?? '')),
                'date_action'=> now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur enregistré avec succès!',
            ], 201); // Code HTTP 201 pour "created"
        } catch (\Exception $e) {
            // 📝 LOG → Inscription échouée
            LogJournalisation::create([
                'action'     => 'Inscription',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => null, // pas encore connecté
                'user_name'  => trim(($input['name'] ?? '') . ' ' . ($input['surname'] ?? '')),
                'date_action'=> now(),
            ]);

            // Si une exception est lancée (par exemple, rôle inexistant), renvoyer une erreur
            Log::error('Erreur lors de l\'inscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500); // Code HTTP 500 pour "Internal Server Error"
        }
    }

    public function getExerciceOuvert()
    {
        // Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'exercice' => $exerciceOuvert,
        ]);
    }





    //-------------------- fonction de login
/*     public function login(LoginRequest $request)
    {
        $input = $request->all();

        $result = $this->authService->login($input);

        if ($result[0]) {
            // LogService::storeLogInfo("Connexion");

            $user = $result[1]['user'];
            // 📝 LOG → Connexion réussie
            LogJournalisation::create([
                'action'     => 'Connexion',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $user->id,
                'date_action'=> now(),
            ]);

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
    } */

/*     public function login(LoginRequest $request)
    {
        $input = $request->all();
        $result = $this->authService->login($input);

        // Récupération du message
        $messageBack = $result[1]['message'] ?? null;

        // Récupération du user en cas de succès ou échec
        $user = $result[1]['user'] ?? null;

        // Si compte inactif, un user peut être fourni en index 2
        if (!$result[0] && $messageBack === 'inactif') {
            $user = $result[2] ?? $user;
        }

        // Extraire infos user
        $userId   = $user?->id;
        //echo "user id est : ".$userId;
        $userName = null;
        $dd="dd";

        // Construction du nom complet si user trouvé
        if ($user) {
            // Logique de récupération du nom (identique à celle du login)
            if ($user->employe) {
                $userName = trim(($user->employe->nom ?? '') . ' ' . ($user->employe->prenom ?? ''));
                //echo "username est" .$userName
            }

            if (empty($userName)) {
                $userFullName = trim(($user->name ?? '') . ' ' . ($user->surname ?? ''));
                $userName = !empty($userFullName) ? $userFullName : ($user->email ?? 'N/A');
            }
        }

        // Action par défaut
        $action = "Tentative échouée";

        // =========================================================
        //  CAS DE CONNEXION RÉUSSIE
        // =========================================================
        if ($result[0]) {

            $action = "Connexion réussie";

            // Log de la réussite
            LogJournalisation::create([
                'action'      => $action,
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->header('User-Agent'),
                'user_id'     => $userId,
                'user_name'   => (string) ($userName ?: $user->email),
                'date_action' => now(),
            ]);


            // Vérification activation du compte
            if ($user && $user->active == 1) {
                return response()->json([
                    'success' => true,
                    'data'    => $result[1],
                    'message' => 'Utilisateur authentifié avec succès! 😁'
                ], 200);
            }

            // Compte inactif après authentification correcte
            return response()->json([
                'success' => false,
                'message' => 'Compte inactif!',
                'errors'  => ['failed' => 'Compte inactif, contactez un administrateur']
            ], 403);
        }

        // =========================================================
        //  CAS DE CONNEXION ÉCHOUÉE
        // =========================================================

        if ($messageBack === 'inactif') {
            $action = "Tentative de connexion (Compte inactif)";
        }

        // Log de l'échec
        LogJournalisation::create([
            'action'      => $action,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->header('User-Agent'),
            'user_id'     => $userId,
            'user_name'   => $userName,
            'date_action' => now(),
        ]);

        // Identifiants incorrects
        if ($messageBack === 'erreurs identifiants') {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects!',
                'errors'  => ['failed' => 'Identifiants incorrects']
            ], 401);
        }

        // Compte inactif
        if ($messageBack === 'inactif') {
            return response()->json([
                'success' => false,
                'message' => 'Compte inactif!',
                'errors'  => ['failed' => 'Votre compte est inactif. Contactez un administrateur']
            ], 403);
        }

        // Fallback — erreur générique (au cas où)
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inconnue est survenue.',
            'errors'  => ['failed' => 'Erreur interne']
        ], 500);
    } */

public function login(LoginRequest $request)
{
    try {
        $input = $request->all();
        $result = $this->authService->login($input);

        $success     = $result[0];
        // $messageBack = $result[1]['message'] ?? null;
        // ✅ Après
$messageBack = is_array($result[1]) ? ($result[1]['message'] ?? null) : $result[1];
        // $user        = $result[1]['user'] ?? $result[2] ?? null;
        // ✅ Après
$user = is_array($result[1]) ? ($result[1]['user'] ?? null) : null;

        $userId   = $user?->id;
        $userName = null;

        if ($user) {
            if ($user->employe) {
                $userName = trim(($user->employe->nom ?? '') . ' ' . ($user->employe->prenom ?? ''));
            }
            if (empty($userName)) {
                $userName = trim(($user->name ?? '') . ' ' . ($user->surname ?? '')) ?: ($user->email ?? 'N/A');
            }
        }

        $action = $success ? 'Connexion réussie' : 'Tentative échouée';

        // Log
        LogJournalisation::create([
            'action'      => $action,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->header('User-Agent'),
            'user_id'     => $userId,
            'user_name'   => (string) ($userName ?: $user?->email),
            'date_action' => now(),
        ]);

        if ($success) {
            if ($user && $user->active == 1) {
                return response()->json([
                    'success' => true,
                    'data'    => $result[1],
                    'message' => 'Utilisateur authentifié avec succès! 😁'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Compte inactif!',
                'errors'  => ['failed' => 'Votre compte est inactif. Contactez un administrateur']
            ], 403);
        }

        // Cas d’échec
        switch ($messageBack) {
            case 'erreurs identifiants':
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects!',
                    'errors'  => ['failed' => 'Identifiants incorrects']
                ], 401);

            case 'inactif':
                return response()->json([
                    'success' => false,
                    'message' => 'Compte inactif!',
                    'errors'  => ['failed' => 'Votre compte est inactif. Contactez un administrateur']
                ], 403);

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur inconnue',
                    'errors'  => [
                        'exception' => $result[1]['exception'] ?? 'Erreur interne',
                        'details'   => $result[1]['message'] ?? 'Erreur interne'
                    ]
                ], 500);
        }
    } catch (\Exception $e) {
        // Attraper toute exception non gérée
        LogJournalisation::create([
            'action'      => 'Erreur login',
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->header('User-Agent'),
            'user_id'     => $request->user()?->id,
            'user_name'   => $request->user()?->name ?? 'N/A',
            'date_action' => now(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur',
            'errors'  => [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]
        ], 500);
    }
}


    //-------------------- Fonction de déconnexion (logout)

/*     public function logout(Request $request)
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
    } */

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentification d’un utilisateur",
     *     description="Permet à un utilisateur de se connecter et d’obtenir un token d'accès.",
     *     tags={"Authentification"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Identifiants de connexion",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Détails de l’utilisateur et token",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi..."),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="name", type="string", example="Jean Dupont"),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="active", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Utilisateur authentifié avec succès! 😁")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants incorrects!"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="failed", type="string", example="Identifiants incorrects")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Compte inactif",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte inactif!"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="failed", type="string", example="Votre compte est inactif. Contactez un administrateur")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur inconnue est survenue."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="failed", type="string", example="Erreur interne")
     *             )
     *         )
     *     )
     * )
     */

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();
            $user = Auth::user();
            $userName = null;

            if ($user && $user->employe) {
                $userName = $user->employe->nom . ' ' . $user->employe->prenom;
            }

            if ($token && $this->authService->logout($token)[0]) {

                // 📝 LOG → Déconnexion
                LogJournalisation::create([
                    'action'     => 'Déconnexion réussie',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => $user?->id,
                    'user_name'  => $userName,
                    'date_action'=> now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Utilisateur déconnecté avec succès! 😁'
                ], 200);
            }

            // LOG → échec déconnexion

                LogJournalisation::create([
                    'action'     => "Tentative déconnexion échouée",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => $user?->id,
                    'user_name'  => $userName,
                    'date_action'=> now(),
                ]);

            return response()->json([
                'success' => false,
                'message' => 'Échec de la déconnexion',
                'errors'  => ['failed' => 'Erreur lors de la déconnexion']
            ], 500);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());

            // LOG → exception logout
            LogJournalisation::create([
                    'action'     => "Erreur déconnexion (exception)",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => $user?->id,
                    'user_name'  => $userName,
                    'date_action'=> now(),
                ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la déconnexion.',
                'errors'  => ['exception' => $e->getMessage()]
            ], 500);
        }
    }



}
