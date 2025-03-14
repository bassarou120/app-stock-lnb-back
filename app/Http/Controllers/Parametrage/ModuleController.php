<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;

use App\Http\Resources\PostResource;
use App\Models\Parametrage\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModuleController extends Controller
{
    // Liste des modules
    public function index()
    {
        $modules = Module::latest()->paginate(200);
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

        return new PostResource(true, 'Module mis à jour avec succès', $module);
    }

    // Suppression d'un module
    public function destroy(Module $module)
    {
        $module->delete();
        return new PostResource(true, 'Module supprimé avec succès', null);
    }
}
