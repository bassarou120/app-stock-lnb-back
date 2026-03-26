<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class TypeImmoController extends Controller
{
    // Afficher la liste des types d'immo
    public function index(Request $request)
    {
        $type_immos = TypeImmo::latest()->where('isdeleted', false)->paginate(10000);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des types d'immo",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des types d\'immos', $type_immos);
    }

    // Créer un nouveau type d'immo
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_typeImmo' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_immo = TypeImmo::create([
            'libelle_typeImmo' => $request->libelle_typeImmo,
            'compte' => $request->compte,
        ]);

        LogJournalisation::create([
            "action"      => "Création du type immo : " . $type_immo->libelle_typeImmo . " (Compte: " . $type_immo->compte . ", ID: " . $type_immo->id . ")",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Type d\'immo créé avec succès', $type_immo);
    }

    // Mettre à jour un type d'immo existant
    public function update(Request $request, TypeImmo $type_immo)
    {
        $validator = Validator::make($request->all(), [
            'libelle_typeImmo' => 'required|string|max:255',
            'compte' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_immo->update([
            'libelle_typeImmo' => $request->libelle_typeImmo,
            'compte' => $request->compte,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour du type immo : " . $type_immo->libelle_typeImmo . " (Compte: " . $type_immo->compte . ", ID: " . $type_immo->id . ")",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Type d\'immo mis à jour avec succès', $type_immo);
    }

    // Supprimer un type d'immo
    public function destroy(TypeImmo $type_immo, Request $request)
    {
        $type_immo->isdeleted = true;
        $type_immo->save();
        LogJournalisation::create([
            "action"      => "Suppression du type immo : " . $type_immo->libelle_typeImmo . " (Compte: " . $type_immo->compte . ", ID: " . $type_immo->id . ")",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Type d\'immo supprimé avec succès', null);
    }
}
