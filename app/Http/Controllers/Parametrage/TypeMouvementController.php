<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\TypeMouvement;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class TypeMouvementController extends Controller
{
    // Afficher la liste des types de mouvement
    public function index(Request $request)
    {
        $typesMouvement = TypeMouvement::latest()->where('isdeleted', false)->paginate(100);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des types de mouvement",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des types de mouvement', $typesMouvement);
    }

    // Créer un nouveau type de mouvement
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_mouvement' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $typeMouvement = TypeMouvement::create([
            'libelle_type_mouvement' => $request->libelle_type_mouvement,
            'valeur' => $request->valeur,
        ]);
        LogJournalisation::create([
            "action"      => "Création du type de mouvement : " . $typeMouvement->libelle_type_mouvement,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Type de mouvement créé avec succès', $typeMouvement);
    }

    // Mettre à jour un type de mouvement existant
    public function update(Request $request, TypeMouvement $typeMouvement)
    {
        $validator = Validator::make($request->all(), [
            'libelle_type_mouvement' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $typeMouvement->update([
            'libelle_type_mouvement' => $request->libelle_type_mouvement,
            'valeur' => $request->valeur,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour du type de mouvement : " . $typeMouvement->libelle_type_mouvement,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Type de mouvement mis à jour avec succès', $typeMouvement);
    }

    // Supprimer un type de mouvement
    public function destroy(TypeMouvement $typeMouvement, Request $request)
    {
        $typeMouvement->isdeleted = true;
        $typeMouvement->save();
        LogJournalisation::create([
            "action"      => "Suppression du type de mouvement : " . $typeMouvement->libelle_type_mouvement,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Type de mouvement supprimé avec succès', null);
    }
}
