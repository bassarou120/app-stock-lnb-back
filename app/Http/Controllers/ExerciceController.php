<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;


class ExerciceController extends Controller
{
    //  Lister tous les exercices
    public function index()
    {
        // return Exercice::all();

        $exercices = Exercice::latest()->paginate(100);

        return new PostResource(true, 'Liste des exercices', $exercices);
    }

    //  Créer un exercice
    public function store(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after:date_debut',
        ]);

        //  Déterminer automatiquement l'année
        $anneeDebut = date('Y', strtotime($request->date_debut));
        $anneeFin   = date('Y', strtotime($request->date_fin));

        if ($anneeDebut !== $anneeFin) {
            return response()->json([
                'message' => "L'exercice doit être sur une seule année (ex: 01/01/2025 au 31/12/2025)"
            ], 422);
        }

        $exercice = Exercice::create([
            'date_debut' => $request->date_debut,
            'date_fin'   => $request->date_fin,
            'annee'      => $anneeDebut,
            'statut'     => 'cloture' // par défaut
        ]);

        return new PostResource(true, 'Type exercice créé avec succès', $exercice);
    }

    //  Modifier un exercice
    public function update(Request $request, $id)
    {
        $exercice = Exercice::findOrFail($id);

        $request->validate([
            'date_debut' => 'sometimes|date',
            'date_fin'   => 'sometimes|date|after:date_debut',
            'statut'     => 'in:ouvert,cloture',
        ]);

        $data = $request->all();

        //  recalculer l'année si date_debut ou date_fin changent
        if ($request->has('date_debut') || $request->has('date_fin')) {
            $dateDebut = $request->date_debut ?? $exercice->date_debut;
            $dateFin   = $request->date_fin ?? $exercice->date_fin;

            $anneeDebut = date('Y', strtotime($dateDebut));
            $anneeFin   = date('Y', strtotime($dateFin));

            if ($anneeDebut !== $anneeFin) {
                return response()->json([
                    'message' => "L'exercice doit être sur une seule année"
                ], 422);
            }

            $data['annee'] = $anneeDebut;
        }

        $exercice->update($data);

        return new PostResource(true, 'exercice modifié avec succès', $exercice);
    }

    //  Supprimer un exercice
    public function destroy($id)
    {
        $exercice = Exercice::findOrFail($id);
        $exercice->delete();

        return new PostResource(true, 'Exercice supprimé avec succès', null);
    }
}