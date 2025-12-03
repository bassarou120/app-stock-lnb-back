<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Role;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    // Afficher une liste paginée des rôles
    public function index(Request $request)
    {
        $roles = Role::latest()->where('isdeleted', false)->paginate(200);

        LogJournalisation::create([
            "action"      => "Affichage de la liste des rôles",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
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

        // Récupérer toutes les fonctionnalités existantes
    $fonctionnalites = \App\Models\Parametrage\Fonctionnalite::all();

    // Créer une permission désactivée (is_active = false) pour chaque fonctionnalité
    foreach ($fonctionnalites as $fonctionnalite) {
        \App\Models\Parametrage\Permission::create([
            'role_id' => $role->id,
            'module_id' => $fonctionnalite->module_id,
            'fonctionnalite_id' => $fonctionnalite->id,
            'is_active' => false,
        ]);
    }

        LogJournalisation::create([
            "action"      => "Création du rôle : " . $role->libelle_role . " (ID: " . $role->id . ")",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
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

        LogJournalisation::create([
            "action"      => "Mise à jour du rôle : " . $role->libelle_role . " (ID: " . $role->id . ")",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Rôle mis à jour avec succès', $role);
    }

    // Supprimer un rôle
    public function destroy(Role $role, Request $request)
    {
        $role->isdeleted = true;
        $role->save();
        LogJournalisation::create([
            "action"      => "Suppression du rôle : " . $role->libelle_role . " (ID: " . $role->id . ")",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Rôle supprimé avec succès', null);
    }
}
