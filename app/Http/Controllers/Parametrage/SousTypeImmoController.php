<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\SousTypeImmo;
use App\Models\Parametrage\TypeImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class SousTypeImmoController extends Controller
{
    // Afficher la liste des sous-types d'immo
    public function index(Request $request)
    {
        $sous_type_immos = SousTypeImmo::with('typeImmo')->where('isdeleted', false)->latest()->paginate(10000);

        LogJournalisation::create([
            "action"      => "Affichage de la liste des sous-types d'immo",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des sous-types d\'immos', $sous_type_immos);
    }

    // Créer un nouveau sous-type d'immo
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_immo' => 'required|exists:type_immos,id',
            'libelle' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sous_type_immo = SousTypeImmo::create([
            'id_type_immo' => $request->id_type_immo,
            'libelle' => $request->libelle,
            'compte' => $request->compte,
        ]);

        LogJournalisation::create([
            "action"      => "Création du sous-type d'immo : " . $sous_type_immo->libelle,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Sous-type d\'immo créé avec succès', $sous_type_immo);
    }

    // Mettre à jour un sous-type d'immo existant
    public function update(Request $request, SousTypeImmo $sous_type_immo)
    {
        $validator = Validator::make($request->all(), [
            'id_type_immo' => 'required|exists:type_immos,id',
            'libelle' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sous_type_immo->update([
            'id_type_immo' => $request->id_type_immo,
            'libelle' => $request->libelle,
            'compte' => $request->compte,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour du sous-type d'immo : " . $sous_type_immo->libelle,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Sous-type d\'immo mis à jour avec succès', $sous_type_immo);
    }

    // Supprimer un sous-type d'immo
    public function destroy(SousTypeImmo $sous_type_immo, Request $request)
    {
        $sous_type_immo->isdeleted = true;
        $sous_type_immo->save();
        LogJournalisation::create([
            "action"      => "Suppression du sous-type d'immo : " . $sous_type_immo->libelle,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Sous-type d\'immo supprimé avec succès', null);
    }
}
