<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeIntervention;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class TypeInterventionController extends Controller
{
    // Afficher la liste des types d'intervention
    public function index(Request $request)
    {
        $types = TypeIntervention::latest()
        ->where('isdeleted', false)
        ->paginate(100000);

        LogJournalisation::create([
            "action"      => "Affichage de la liste des types d'intervention",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Liste des types d\'intervention', $types);
    }

    // Créer un nouveau type d'intervention
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_intervention' => 'required|string|max:255',
            'applicable_seul_vehicule' => 'required|boolean',
            'observation' => 'nullable|string|max:255',
            'has_expiration_date' => 'required|boolean',
            // 'date_expiration' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type = TypeIntervention::create([
            'libelle_type_intervention' => $request->libelle_type_intervention,
            'applicable_seul_vehicule' => $request->applicable_seul_vehicule,
            'observation' => $request->observation,
            'has_expiration_date' => $request->has_expiration_date,
            // 'date_expiration' => $request->date_expiration,
        ]);

        LogJournalisation::create([
            "action"      => "Création du type d'intervention : " . $type->libelle_type_intervention,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Type d\'intervention créé avec succès', $type);
    }

    // Mettre à jour un type d'intervention existant
    public function update(Request $request, TypeIntervention $type_intervention)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_intervention' => 'required|string|max:255',
            'applicable_seul_vehicule' => 'required|boolean',
            'observation' => 'nullable|string|max:255',
            'has_expiration_date' => 'required|boolean',
            // 'date_expiration' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_intervention->update([
            'libelle_type_intervention' => $request->libelle_type_intervention,
            'applicable_seul_vehicule' => $request->applicable_seul_vehicule,
            'observation' => $request->observation,
            'has_expiration_date' => $request->has_expiration_date,
            // 'date_expiration' => $request->date_expiration,
        ]);
        LogJournalisation::create([
            "action"      => "Mise à jour du type d'intervention : " . $type_intervention->libelle_type_intervention,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Type d\'intervention mis à jour avec succès', $type_intervention);
    }

    // Supprimer un type d'intervention
    public function destroy(TypeIntervention $type_intervention, Request $request)
    {
        $type_intervention->isdeleted = true;
        $type_intervention->save();
        LogJournalisation::create([
            "action"      => "Suppression du type d'intervention : " . $type_intervention->libelle_type_intervention,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Type d\'intervention supprimé avec succès', null);
    }
}
