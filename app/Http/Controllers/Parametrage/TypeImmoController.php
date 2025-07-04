<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class TypeImmoController extends Controller
{
    // Afficher la liste des types d'immo
    public function index()
    {
        $type_immos = TypeImmo::latest()->where('isdeleted', false)->paginate(100);
        return new PostResource(true, 'Liste des types d\'immos', $type_immos);
    }

    // Créer un nouveau type d'immo
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_typeImmo' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_immo = TypeImmo::create([
            'libelle_typeImmo' => $request->libelle_typeImmo,
            'compte' => $request->compte,
        ]);

        return new PostResource(true, 'Type d\'immo créé avec succès', $type_immo);
    }

    // Mettre à jour un type d'immo existant
    public function update(Request $request, TypeImmo $type_immo)
    {
        $validator = Validator::make($request->all(), [
            'libelle_typeImmo' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_immo->update([
            'libelle_typeImmo' => $request->libelle_typeImmo,
            'compte' => $request->compte,
        ]);

        return new PostResource(true, 'Type d\'immo mis à jour avec succès', $type_immo);
    }

    // Supprimer un type d'immo
    public function destroy(TypeImmo $type_immo)
    {
        $type_immo->isdeleted = true;
        $type_immo->save();
        return new PostResource(true, 'Type d\'immo supprimé avec succès', null);
    }
}
