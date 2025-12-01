<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Modele;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class ModeleController extends Controller
{
    // Afficher la liste des modèles
    public function index(Request $request)
    {
        $modeles = Modele::latest()->where('isdeleted', false)->paginate(100);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des modèles",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Liste des modèles', $modeles);
    }

    // Créer un nouveau modèle
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $modele = Modele::create([
            'libelle_modele' => $request->libelle,
        ]);
        
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $modele->update([
            'libelle_modele' => $request->libelle,
        ]);

        LogJournalisation::create([
            "action"      => "Création de modèle",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        
        return new PostResource(true, 'Modèle créé avec succès', $modele);
    }

    // Supprimer un modèle
    public function destroy(Modele $modele, Request $request)
    {
        $modele->isdeleted = true;
        $modele->save();
        LogJournalisation::create([
            "action"      => "Suppression de modèle ID: " . $modele->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        return new PostResource(true, 'Modèle supprimé avec succès', null);
    }
}
