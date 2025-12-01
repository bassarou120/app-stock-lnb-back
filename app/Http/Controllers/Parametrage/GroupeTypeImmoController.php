<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;


class GroupeTypeImmoController extends Controller
{
    // Afficher la liste des groupes de type immo
    public function index(Request $request)
    {
        $groupe_type_immos = GroupeTypeImmo::latest()->where('isdeleted', false)->paginate(1000);

        LogJournalisation::create([
            "action"      => "Affichage de la liste des groupes de type immo",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Liste des groupes de type immmo', $groupe_type_immos);
    }

    // Créer un nouveau groupe de type immo
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $groupe_type_immo = GroupeTypeImmo::create([
            'libelle' => $request->libelle,
            'compte' => $request->compte,
        ]);

        LogJournalisation::create([
            "action"      => "Création de groupe de type immo",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Groupe de type immo créé avec succès', $groupe_type_immo);
    }

    // Mettre à jour un groupe de type immo existant
    public function update(Request $request, GroupeTypeImmo $groupe_type_immo)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $groupe_type_immo->update([
            'libelle' => $request->libelle,
            'compte' => $request->compte,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour de groupe de type immo ID: " . $groupe_type_immo->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        return new PostResource(true, 'Groupe de type immo mis à jour avec succès', $groupe_type_immo);
    }

    // Supprimer un groupe de type immo
    public function destroy(GroupeTypeImmo $groupe_type_immo, Request $request)
    {
        $groupe_type_immo->isdeleted = true;
        $groupe_type_immo->save();
        
        LogJournalisation::create([
            "action"      => "Suppression de groupe de type immo ID: " . $groupe_type_immo->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        
        return new PostResource(true, 'Groupe de type immo supprimé avec succès', null);
    }
}
