<?php

namespace App\Http\Controllers\Parametrage;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Fournisseur;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

class FournisseurController extends Controller
{
    // Afficher la liste des fournisseurs
    public function index()
    {
        $fournisseurs = Fournisseur::latest()->where('isdeleted', false)->paginate(100);
        return new PostResource(true, 'Liste des fournisseurs', $fournisseurs);
    }

    // Créer un nouveau fournisseur
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $fournisseur = Fournisseur::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
        ]);

        return new PostResource(true, 'Fournisseur créé avec succès', $fournisseur);
    }

    // Mettre à jour un fournisseur existant
    public function update(Request $request, Fournisseur $fournisseur)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $fournisseur->update([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
        ]);

        return new PostResource(true, 'Fournisseur mis à jour avec succès', $fournisseur);
    }

    // Supprimer un fournisseur
    public function destroy(Fournisseur $fournisseur)
    {
        $fournisseur->isdeleted = true;
        $fournisseur->save();
        return new PostResource(true, 'Fournisseur supprimé avec succès', null);
    }

    public function imprimer()
    {
        $fournisseurs = Fournisseur::all()->where('isdeleted', false);

        $pdf = Pdf::loadView('pdf.fournisseurs', compact('fournisseurs'));

        return $pdf->download('liste_fournisseurs.pdf');
    }


}
