<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;
use App\Models\Parametrage\UniteDeMesure;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class UniteDeMesureController extends Controller
{
    // Afficher la liste des UniteDeMesures
    public function index()
    {
        $uniteDeMesures = UniteDeMesure::latest()->where('isdeleted', false)->paginate(100);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des Unites De Mesure",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des Unites De Mesure', $uniteDeMesures);
    }

    // Créer un nouveau UniteDeMesures
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $uniteDeMesure = UniteDeMesure::create([
            'libelle' => $request->libelle,
        ]);
        LogJournalisation::create([
            "action"      => "Création d'un nouveau Unite De Mesure",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'unite De Mesure créé avec succès', $uniteDeMesure);
    }

    // Mettre à jour un uniteDeMesure existant
    public function update(Request $request, UniteDeMesure $unite_de_mesure)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $unite_de_mesure->update([
            'libelle' => $request->libelle,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour d'un Unite De Mesure",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'UniteDeMesure mis à jour avec succès', $unite_de_mesure);
    }

    // Supprimer un unite_de_mesure
    public function destroy(UniteDeMesure $unite_de_mesure)
    {
        $unite_de_mesure->isdeleted = true;
        $unite_de_mesure->save();
        LogJournalisation::create([
            "action"      => "Suppression d'un Unite De Mesure",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'unite_de_mesure supprimé avec succès', null);
    }
}
