<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\Permission;
use App\Models\Parametrage\Role;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{

    public function index(Request $request)
    {
        // Récupérer toutes les permissions avec leurs relations
        $permissions = Permission::where('isdeleted', false)
            ->with([
                'role',
                'module',
                'fonctionnalite',
                'fonctionnalite.module' // 🔥 Charger le module de la fonctionnalité
            ])
            ->get();

        // Filtrer les permissions valides
        $validPermissions = $permissions->filter(function ($permission) {
            return $permission->role !== null 
                && $permission->module !== null 
                && $permission->fonctionnalite !== null
                && $permission->fonctionnalite->module !== null // 🔥 Vérifier le module de la fonctionnalité
                && $permission->role->isdeleted == false
                && $permission->module->isdeleted == false
                && $permission->fonctionnalite->isdeleted == false;
        });

        // 🔥 TRI CRUCIAL : Trier par role_id, puis par le module_id de la fonctionnalité
        $sortedPermissions = $validPermissions->sortBy([
            ['role.id', 'asc'],
            ['fonctionnalite.module.id', 'asc'], // 🔥 Trier par le module de la fonctionnalité
            ['fonctionnalite.id', 'asc']
        ])->values();

        LogJournalisation::create([
            "action"      => "Affichage de la liste des permissions",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Liste des permissions',
            'data'    => $sortedPermissions
        ]);
    }

    // Créer une nouvelle permission
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'module_id' => 'required|exists:modules,id',
            'fonctionnalite_id' => 'required|exists:fonctionnalites,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer une permission
        $permission = Permission::create([
            'role_id' => $request->role_id,
            'module_id' => $request->module_id,
            'fonctionnalite_id' => $request->fonctionnalite_id,
            'is_active' => $request->is_active,
        ]);

        LogJournalisation::create([
            "action"      => "Création de permission",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Permission créée avec succès', $permission);
    }

    // Mettre à jour une permission
    public function update(Request $request, Permission $permission)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'module_id' => 'required|exists:modules,id',
            'fonctionnalite_id' => 'required|exists:fonctionnalites,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mise à jour de la permission
        $permission->update([
            'role_id' => $request->role_id,
            'module_id' => $request->module_id,
            'fonctionnalite_id' => $request->fonctionnalite_id,
            'is_active' => $request->is_active,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour de permission ID: " . $permission->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Permission mise à jour avec succès', $permission);
    }

    // Supprimer une permission
    public function destroy(Permission $permission, Request $request)
    {
        $permission->isdeleted = true;
        $permission->save();
        LogJournalisation::create([
            "action"      => "Suppression de permission ID: " . $permission->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'Permission supprimée avec succès', null);
    }

    public function togglePermission(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'module_id' => 'required|exists:modules,id',
            'fonctionnalite_id' => 'required|exists:fonctionnalites,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(), 422);
        }

        $data = $validated->validated();

        // Crée ou met à jour la permission
        $permission = \App\Models\Parametrage\Permission::updateOrCreate(
            [
                'role_id' => $data['role_id'],
                'module_id' => $data['module_id'],
                'fonctionnalite_id' => $data['fonctionnalite_id'],
            ],
            [
                'is_active' => $data['is_active'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission mise à jour avec succès',
            'data' => $permission,
        ]);
    }

    public function getByRole($roleId)
    {
        $permissions = Permission::with(['module', 'fonctionnalite', 'role'])
            ->where('role_id', $roleId)
            ->where('isdeleted', false)
            ->get();

        return response()->json($permissions);
    }
}
