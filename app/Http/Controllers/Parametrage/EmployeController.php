<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Employe;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;


class EmployeController extends Controller
{
    // Afficher la liste des Employe
    public function index()
    {
        $employes = Employe::latest()->paginate(500);
        return new PostResource(true, 'Liste des employés', $employes);
    }

    // Créer un nouveau Employe
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $employe = Employe::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);

        return new PostResource(true, 'Employe créé avec succès', $employe);
    }

    // Mettre à jour un Employe existant
    public function update(Request $request, Employe $employe)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $employe->update([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);

        return new PostResource(true, 'Employé mis à jour avec succès', $employe);
    }

    // Supprimer un Employe
    public function destroy(Employe $employe)
    {
        $employe->delete();
        return new PostResource(true, 'Employe supprimé avec succès', null);
    }

    public function imprimer()
    {
        $employes = Employe::all();

        $pdf = Pdf::loadView('pdf.employes', compact('employes'));

        return $pdf->download('liste_personnels.pdf');
    }
}
