<?php

namespace App\Services\Auth;
use App\Mail\UserRegisteredMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Loginfo;
use App\Models\Module;
use App\Models\Parametrage\Role;
use App\Models\User;
use App\Services\GeneralService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService extends Controller
{
    protected GeneralService $generalService;

    public function __construct(GeneralService $generalService)
    {
        $this->generalService = $generalService;
    }

    public function login(array $data)
    {
        if (Auth::attempt([User::EMAIL => $data['email'],  User::PASSWORD => $data['password']])) {
            $user = User::find(Auth::user()->id);
            if (isset($user) && $user->active == 0) {
                // Loginfo::create(
                //     [
                //         'action' => 'Tentative de connexion avec le mail' . $data['email'],
                //     ]
                // );
                return [false, 'inactif'];
            } else {
                $token = $user->createToken("AuthToken")->accessToken;
                $user = User::find(Auth::id());
                $success['token'] = $token;
                $success['user'] = $user->load("role");
                $role = Role::firstWhere('id', $success['user']['role_id']);
                $permission = $role->permissions()->with(["module", "fonctionnalite"])->get();
                $success['perm'] = $permission;
                $user->last_activity = now();
                $user->save();
                // Loginfo::create(
                //     [
                //         'action' => 'Connexion de l\'utilisateur ' . Auth::user()->name . ' ' . Auth::user()->surname,
                //         'user' => $user->id
                //     ]
                // );
                return [true, $success];
            }
        } else {
            return [false, 'erreurs identifiants'];
        }
    }

    public function logout($token)
    {
        try {
            // Récupérer l'utilisateur du token
            $user = Auth::guard('api')->user(); // Cela suppose que l'utilisateur est authentifié via ce token

                 // Vérifie si l'utilisateur est trouvé
                 if (!$user) {
                    return [false, 'Utilisateur non trouvé pour ce token.'];
                }

                // Supprimer tous les tokens de l'utilisateur
                $user->tokens->each(function ($token) {
                    $token->delete();
                });

                // Enregistrer l'action de déconnexion dans les logs
                // $this->logService->logAction(
                //     'Déconnexion de l\'utilisateur ' . $user->name . ' ' . $user->surname,
                //     $user->id
                // );

                return [true, 'Déconnecté avec succès!'];


        } catch (\Exception $e) {
            // En cas d'erreur, log l'exception
            Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
            return [false, 'Erreur lors de la déconnexion.'];
        }
    }


    public function register(array $data):User
    {
        $password = $this->generalService->generateStrongPassword(8);
        $hashedPassword = Hash::make($password);

        // Récupérer l'ID du rôle 'Collaborateur RH'
        $role = Role::where('libelle_role', 'Manager')->first();

        // Si le rôle n'existe pas, on peut gérer l'erreur ou attribuer un rôle par défaut
        if (!$role) {
            throw new \Exception("Le rôle que vous comptez donner à l'utilisateur n'existe pas.");
        }

        $arr = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'active' => $data['active'],
            'sexe' => $data['sexe'],
            'role_id' => $role->id,
            'password' => $hashedPassword,
        ];
        $user = User::create($arr);
        // Envoi de l'e-mail avec le mot de passe en clair
        Mail::to($user->email)->send(new UserRegisteredMail($user, $password));

        return $user;
    }

    // public function resetPassword(User $user)
    // {
    //     $password = $this->generalService->generateStrongPassword(8);
    //     $crypted = Hash::make($password);
    //     $user->password = $crypted;
    //     $user->save();
    //     $user->tokens()->delete();
    // }
}
