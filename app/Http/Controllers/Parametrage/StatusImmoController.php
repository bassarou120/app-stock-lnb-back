<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\StatusImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class StatusImmoController extends Controller
{
    // Afficher la liste des statuts immobiliers
    public function index()
    {
        $status_immos = StatusImmo::latest()->paginate(100);
        return new PostResource(true, 'Liste des statuts immobiliers', $status_immos);
    }

    // Créer un nouveau statut immobilier
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_status_immo' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $status_immo = StatusImmo::create([
            'libelle_status_immo' => $request->libelle_status_immo,
        ]);

        return new PostResource(true, 'Statut immobilier créé avec succès', $status_immo);
    }

    // Mettre à jour un statut immobilier existant
    public function update(Request $request, StatusImmo $status_immo)
    {
        $validator = Validator::make($request->all(), [
            'libelle_status_immo' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $status_immo->update([
            'libelle_status_immo' => $request->libelle_status_immo,
        ]);

        return new PostResource(true, 'Statut immobilier mis à jour avec succès', $status_immo);
    }

    // Supprimer un statut immobilier
    public function destroy(StatusImmo $status_immo)
    {
        $status_immo->delete();
        return new PostResource(true, 'Statut immobilier supprimé avec succès', null);
    }
}
