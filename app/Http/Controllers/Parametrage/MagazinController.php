<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Magazin;
use App\Http\Resources\PostResource; 
use Illuminate\Support\Facades\Validator;

class MagazinController extends Controller
{
    // Afficher la liste des magasins
    public function index()
    {
        $magazins = Magazin::latest()->paginate(100);

        return new PostResource(true, 'Liste des magasins', $magazins);
    }

    // Créer un nouveau magasin
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_magazin' => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $magazin = Magazin::create([
            'libelle_magazin' => $request->libelle_magazin,
            'localisation' => $request->localisation,
        ]);

        return new PostResource(true, 'Magasin créé avec succès', $magazin);
    }

    // Mettre à jour un magasin existant
    public function update(Request $request, Magazin $magazin)
    {
        $validator = Validator::make($request->all(), [
            'libelle_magazin' => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $magazin->update([
            'libelle_magazin' => $request->libelle_magazin,
            'localisation' => $request->localisation,
        ]);

        return new PostResource(true, 'Magasin mis à jour avec succès', $magazin);
    }

    // Supprimer un magasin
    public function destroy(Magazin $magazin)
    {
        $magazin->delete();

        return new PostResource(true, 'Magasin supprimé avec succès', null);
    }
}
