<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with(['role', 'module', 'fonctionnalite'])->latest()->paginate(200);
        return new PostResource(true, 'Liste des permissions', $permissions);
    }
//     public function index()
// {
//     $permissions = Permission::with([
//         'role:id,libelle_role',
//         'module:id,libelle_module',
//         'fonctionnalite:id,libelle_fonctionnalite'
//     ])->select('id', 'role_id', 'module_id', 'fonctionnalite_id', 'is_active', 'created_at', 'updated_at')
//       ->latest()
//       ->paginate(200);

//     return new PostResource(true, 'Liste des permissions', $permissions);
// }

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

        return new PostResource(true, 'Permission mise à jour avec succès', $permission);
    }

    // Supprimer une permission
    public function destroy(Permission $permission)
    {
        $permission->delete();
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
                            ->get();

    return response()->json($permissions);
}


}
