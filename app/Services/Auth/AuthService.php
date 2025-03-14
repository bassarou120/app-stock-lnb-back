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

    public function __construct()
    {
        $this->generalService = new GeneralService();
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

    public function logout()
    {
        $user = Auth::user();
        $user->tokens->each(function ($token) {
            $token->delete();
        });
        // Loginfo::create(
        //     [
        //         'action' => 'Déconnexion de l\'utilisateur ' . Auth::user()->name . ' ' . Auth::user()->surname,
        //         'user' => Auth::user()->id
        //     ]
        // );

        return [true, 'Déconnecté avec succès!'];
    }


    public function register(array $data)
    {
        $password = $this->generalService->generateStrongPassword(8);
        $pass = Hash::make($password);

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
            'password' => $pass,
        ];
        $user = User::create($arr);
        // Envoi de l'e-mail avec le mot de passe en clair
        Mail::to($user->email)->send(new UserRegisteredMail($user, $password));
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
