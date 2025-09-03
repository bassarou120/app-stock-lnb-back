<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Carbon\Carbon;


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

        // Déterminer l'année à partir des dates
        $anneeDebut = date('Y', strtotime($request->date_debut));
        $anneeFin   = date('Y', strtotime($request->date_fin));

        if ($anneeDebut !== $anneeFin) {
            return response()->json([
                'message' => "L'exercice doit être sur une seule année (ex: 01/01/2025 au 31/12/2025)"
            ], 422);
        }

        // 1. Déterminer l'année en cours pour la comparaison
        $anneeActuelle = Carbon::now()->year;

        // 2. Définir le statut par défaut
        // S'il s'agit de l'année en cours, le statut est 'ouvert', sinon il est 'cloture'.
        $statut = ($anneeDebut == $anneeActuelle) ? 'ouvert' : 'cloture';

        // 3. Si le nouvel exercice est "ouvert", fermer tous les autres exercices
        if ($statut === 'ouvert') {
            Exercice::where('statut', 'ouvert')->update(['statut' => 'cloture']);
        }
        
        // 4. Créer le nouvel exercice avec le statut déterminé
        $exercice = Exercice::create([
            'date_debut' => $request->date_debut,
            'date_fin'   => $request->date_fin,
            'annee'      => $anneeDebut,
            'statut'     => $statut
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

    public function changeStatus(Request $request, $id)
    {
        // Valider le statut reçu
        $request->validate([
            'statut' => 'required|in:ouvert,cloture',
        ]);
        
        $nouvStatut = $request->input('statut');

        // Récupérer l'exercice à mettre à jour
        $exercice = Exercice::findOrFail($id);
        
        // Logique pour s'assurer qu'un seul exercice est ouvert à la fois
        if ($nouvStatut === 'ouvert') {
            // Clôturer tous les autres exercices si le statut est "ouvert"
            Exercice::where('id', '!=', $id)->update(['statut' => 'cloture']);
        }

        // Mettre à jour le statut de l'exercice sélectionné
        $exercice->statut = $nouvStatut;
        $exercice->save();

        return new PostResource(true, 'Statut de l\'exercice mis à jour avec succès', $exercice);
    }

    //  Supprimer un exercice
    public function destroy($id)
    {
        $exercice = Exercice::findOrFail($id);
        $exercice->delete();

        return new PostResource(true, 'Exercice supprimé avec succès', null);
    }
}