<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;

use App\Http\Resources\PostResource;
use App\Models\Parametrage\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    // Liste des modules
    public function index(Request $request)
    {
        $modules = Module::latest()->where('isdeleted', false)->paginate(200);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des modules",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des modules', $modules);
    }

    // Création d'un module
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "libelle_module" => 'required|unique:modules,libelle_module',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $module = Module::create([
            "libelle_module" => $request->libelle_module,
        ]);

        LogJournalisation::create([
            "action"      => "Création de module",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Module enregistré avec succès', $module);
    }

    // Mise à jour d'un module
    public function update(Request $request, Module $module)
    {
        $validator = Validator::make($request->all(), [
            "libelle_module" => 'required|unique:modules,libelle_module,' . $module->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $module->update([
            "libelle_module" => $request->libelle_module,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour de module ID: " . $module->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Module mis à jour avec succès', $module);
    }

    // Suppression d'un module
    public function destroy(Module $module, Request $request)
    {
        $module->isdeleted = true;
        $module->save();

        LogJournalisation::create([
            "action"      => "Suppression de module ID: " . $module->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        
        return new PostResource(true, 'Module supprimé avec succès', null);
    }
}
