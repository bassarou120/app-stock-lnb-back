<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Role;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    // Afficher une liste paginée des rôles
    public function index()
    {
        $roles = Role::latest()->paginate(200);
        return new PostResource(true, 'Liste des rôles', $roles);
    }

    // Créer un nouveau rôle
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'libelle_role' => 'required|unique:roles,libelle_role',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer un rôle
        $role = Role::create([
            'libelle_role' => $request->libelle_role,
        ]);

        return new PostResource(true, 'Rôle créé avec succès', $role);
    }

    // Mettre à jour un rôle
    public function update(Request $request, Role $role)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'libelle_role' => 'required|unique:roles,libelle_role,' . $role->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mise à jour du rôle
        $role->update([
            'libelle_role' => $request->libelle_role,
        ]);

        return new PostResource(true, 'Rôle mis à jour avec succès', $role);
    }

    // Supprimer un rôle
    public function destroy(Role $role)
    {
        $role->delete();
        return new PostResource(true, 'Rôle supprimé avec succès', null);
    }
}
