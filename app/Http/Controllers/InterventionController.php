<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\TypeIntervention;
<<<<<<< HEAD

=======
>>>>>>> roland_dev


class InterventionController extends Controller
{
   // Afficher la liste des intervention
   public function index()
   {
       $interventions = Intervention::with([
           'typeIntervention',
           'immobilisation',
       ])->latest()->paginate(100);

       return new PostResource(true, 'Liste des interventions', $interventions);
   }

<<<<<<< HEAD
   public function Intervention_immo()
=======
    public function Intervention_immo()
>>>>>>> roland_dev
    {
        $interventions = TypeIntervention::where("applicable_seul_vehicule", false)
        ->latest()
        ->paginate(100);

    return new PostResource(true, 'Liste des interventions immos', $interventions);
    }

   // Créer une nouvelle intervention
   public function store(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'immo_id' => 'required|exists:immobilisations,id',
           'type_intervention_id' => 'required|exists:type_interventions,id',
           'titre' => 'required|string|max:255',
           'observation' => 'nullable|string|max:255',
           'date_intervention' => 'required|date',
           'cout' => 'required|integer',
       ]);

       if ($validator->fails()) {
           return response()->json($validator->errors(), 422);
       }

       $intervention = Intervention::create($request->all());

       return new PostResource(true, 'intervention créée avec succès', $intervention);
   }

   // Mettre à jour une intervention existante
   public function update(Request $request, Intervention $intervention)
   {
       $validator = Validator::make($request->all(), [
            'immo_id' => 'required|exists:immobilisations,id',
           'type_intervention_id' => 'required|exists:type_interventions,id',
           'titre' => 'required|string|max:255',
           'observation' => 'nullable|string|max:255',
           'date_intervention' => 'required|date',
           'cout' => 'required|integer',
       ]);

       if ($validator->fails()) {
           return response()->json($validator->errors(), 422);
       }

       $intervention->update($request->all());

       return new PostResource(true, 'intervention mise à jour avec succès', $intervention);
   }

   // Supprimer une intervention
   public function destroy(Intervention $intervention)
   {
       $intervention->delete();

       return new PostResource(true, 'intervention supprimée avec succès', null);
   }

   public function imprimerInterventions()
    {
        $interventions = Intervention::with([
            'typeIntervention',
            'immobilisation',
        ])->latest()->get();

        $pdf = \Pdf::loadView('pdf.interventions', compact('interventions'));

        return $pdf->download('liste_interventions.pdf');
    }
}
