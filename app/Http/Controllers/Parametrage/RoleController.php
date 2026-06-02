<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\LogJournalisation;
use App\Models\Parametrage\Fonctionnalite;
use App\Models\Parametrage\Module;
use App\Models\Parametrage\Permission;
use App\Models\Parametrage\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    // Afficher une liste paginée des rôles
    public function index(Request $request)
    {
        $roles = Role::latest()->where('isdeleted', false)->paginate(200);

        $user = $request->user();

        LogJournalisation::create([
            'action' => 'Affichage de la liste des rôles',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Invité',
            'date_action' => now(),
        ]);

        return new PostResource(true, 'Liste des rôles', $roles);
    }

    // Créer un nouveau rôle
    // public function store(Request $request)
    // {
    //     $role = Role::create([
    //         'libelle_role' => $request->libelle_role,
    //         'isdeleted' => false,
    //     ]);

    //     // Charger tous les modules et fonctionnalités
    //     $modules = Module::where('isdeleted', false)->get();
    //     $fonctions = Fonctionnalite::where('isdeleted', false)->get();

    //     foreach ($modules as $module) {
    //         foreach ($fonctions as $fonction) {
    //             Permission::create([
    //                 'role_id' => $role->id,
    //                 'module_id' => $module->id,
    //                 'fonctionnalite_id' => $fonction->id,
    //                 'is_active' => false,
    //                 'isdeleted' => false,
    //             ]);
    //         }
    //     }

    //     return new PostResource(true, 'Rôle créé avec ses permissions', $role);
    // }

    public function store(Request $request)
    {
        $role = Role::create([
            'libelle_role' => $request->libelle_role,
            'isdeleted' => false,
        ]);

        // Charger tous les modules et fonctionnalités
        $modules = Module::where('isdeleted', false)->get();
        foreach ($modules as $module) {

            $fonctions = Fonctionnalite::where('module_id', $module->id)
                ->where('isdeleted', false)
                ->get();

            foreach ($fonctions as $fonction) {

                Permission::create([
                    'role_id' => $role->id,
                    'module_id' => $module->id,
                    'fonctionnalite_id' => $fonction->id,
                    'is_active' => false,
                    'isdeleted' => false,
                ]);
            }
        }

        return new PostResource(true, 'Rôle créé avec ses permissions', $role);
    }

    // Mettre à jour un rôle
    public function update(Request $request, Role $role)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'libelle_role' => 'required|unique:roles,libelle_role,'.$role->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mise à jour du rôle
        $role->update([
            'libelle_role' => $request->libelle_role,
        ]);

        LogJournalisation::create([
            'action' => 'Mise à jour du rôle : '.$role->libelle_role.' (ID: '.$role->id.')',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'date_action' => now(),
        ]);

        return new PostResource(true, 'Rôle mis à jour avec succès', $role);
    }

    // Supprimer un rôle
    public function destroy(Role $role, Request $request)
    {
        $role->isdeleted = true;
        $role->save();
        LogJournalisation::create([
            'action' => 'Suppression du rôle : '.$role->libelle_role.' (ID: '.$role->id.')',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'date_action' => now(),
        ]);

        return new PostResource(true, 'Rôle supprimé avec succès', null);
    }
}
