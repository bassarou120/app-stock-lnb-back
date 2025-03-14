<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Bureau;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

//Attention ! Attention ! Attention ! Attention ! Attention ! Attention !
//Update & Destroy demandent "bureaux" et non bureau
//Voir les paramètres des fonctions update et destroy

class BureauController extends Controller
{
    // Afficher la liste des bureaux
    public function index()
    {
        $bureaux = Bureau::latest()->paginate(100);
        return new PostResource(true, 'Liste des bureaux', $bureaux);
    }

    // Créer un nouveau bureau
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_bureau' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bureau = Bureau::create([
            'libelle_bureau' => $request->libelle_bureau,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Bureau créé avec succès', $bureau);
    }

    // Mettre à jour un bureau existant
    public function update(Request $request, Bureau $bureaux)
    {
        $validator = Validator::make($request->all(), [
            'libelle_bureau' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bureaux->update([
            'libelle_bureau' => $request->libelle_bureau,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Bureau mis à jour avec succès', $bureaux);
    }

    // Supprimer un bureau
    public function destroy(Bureau $bureaux)
    {
        $bureaux->delete();
        return new PostResource(true, 'Bureau supprimé avec succès', null);
    }
}
