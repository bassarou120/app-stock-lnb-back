<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Parametrage\MouvementTicket;
use App\Models\Parametrage\Commune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PostResource;

class TrajetController extends Controller
{
    // Afficher tous les trajets
    public function index()
    {
        $trajet = Trajet::with([
            'depart',
            'arriver'
        ])
        ->where('isdeleted', false)
        ->latest()->paginate(1000);

        return new PostResource(true, 'Liste des trajets', $trajet);
    }

    // Créer un trajet
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commune_depart' => 'required|exists:communes,id',
            'commune_arriver' => 'required|exists:communes,id',
            'trajet_aller_retour' => 'required|boolean',
            'valeur' => 'required|integer',
            'observation' => 'nullable|string',
        ]);

        $trajet = Trajet::create($validated);
        return response()->json($trajet, 201);
    }

    // Afficher un trajet spécifique
    public function show($id)
    {
        $trajet = Trajet::with(['mouvementTickets', 'depart', 'arriver'])->findOrFail($id);
        return response()->json($trajet);
    }

    // Mettre à jour un trajet
    public function update(Request $request, $id)
    {
        $trajet = Trajet::findOrFail($id);

        $validated = $request->validate([ 
            'commune_depart' => 'sometimes|required|exists:communes,id',
            'commune_arriver' => 'sometimes|required|exists:communes,id',
            'trajet_aller_retour' => 'sometimes|required|boolean',
            'observation' => 'nullable|string',
            'valeur' => 'required|integer',
        ]);

        $trajet->update($validated);
        return response()->json($trajet);
    }

    // Supprimer un trajet
    public function destroy($id)
    {
        $trajet = Trajet::findOrFail($id);
        $trajet->isdeleted = true;
        $trajet->save();

        return response()->json(['message' => 'Trajet supprimé avec succès']);
    }
}
