<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrage\TypeIntervention;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class InterventionController extends Controller
{
   // Afficher la liste des intervention
   public function index()
   {
       $interventions = Intervention::with([
           'typeIntervention',
           'immobilisation',
       ])
       ->where('isdeleted', false)
       ->latest()->paginate(100);

           // 📝 LOG → Consultation de la liste des interventions
        LogJournalisation::create([
            'action'     => 'Consultation de la liste des interventions',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id() ?? null,
            'date_action'=> now(),
        ]);

       return new PostResource(true, 'Liste des interventions', $interventions);
   }

    public function Intervention_immo()
    {
        $interventions = TypeIntervention::where("applicable_seul_vehicule", false)
        ->latest()
        ->where('isdeleted', false)
        ->paginate(100);

        // 📝 LOG → Consultation des interventions "immos"
        LogJournalisation::create([
            'action'     => 'Consultation des interventions immos',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id() ?? null,
            'date_action'=> now(),
        ]);

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

        LogJournalisation::create([
            'action'     => 'Création d\'une intervention ID ' . $intervention->id . ' (Titre: ' . $intervention->titre . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id() ?? null,
            'date_action'=> now(),
        ]);

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

        LogJournalisation::create([
            'action'     => 'Mise à jour de l\'intervention ID ' . $intervention->id . ' (Titre: ' . $intervention->titre . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id() ?? null,
            'date_action'=> now(),
        ]);

       return new PostResource(true, 'intervention mise à jour avec succès', $intervention);
   }

   // Supprimer une intervention
   public function destroy(Intervention $intervention)
   {
       $intervention->isdeleted = true;
       $intervention->save();

        LogJournalisation::create([
            'action'     => 'Suppression de l\'intervention ID ' . $intervention->id . ' (Titre: ' . $intervention->titre . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id() ?? null,
            'date_action'=> now(),
        ]);

       return new PostResource(true, 'intervention supprimée avec succès', null);
   }

   public function imprimerInterventions()
    {
        $interventions = Intervention::with([
            'typeIntervention',
            'immobilisation',
        ])->where('isdeleted', false)
        ->latest()->get();

        $pdf = \Pdf::loadView('pdf.interventions', compact('interventions'));
        LogJournalisation::create([
                'action'     => 'Impression de la liste des interventions',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
        return $pdf->download('liste_interventions.pdf');
    }
}
