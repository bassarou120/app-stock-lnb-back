<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Marque;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class MarqueController extends Controller
{
    // Afficher la liste des marques
    public function index()
    {
        // Récupérer toutes les marques triées par ordre décroissant
        $marques = Marque::latest()->where('isdeleted', false)->paginate(100);

        LogJournalisation::create([
            "action"      => "Affichage de la liste des marques",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        
        // Retourner la réponse formatée avec MarqueResource
        return new PostResource(true, 'Liste des marques', $marques);
    }

    // Créer une nouvelle marque
    public function store(Request $request)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer une nouvelle marque avec les données valides
        $marque = Marque::create([
            'libelle' => $request->libelle,
        ]);
        
        LogJournalisation::create([
            "action"      => "Création de marque",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        
        // Retourner la réponse formatée avec MarqueResource, indiquant que la création a réussi
        return new PostResource(true, 'Marque créée avec succès !', $marque);
    }

    // Mettre à jour une marque existante
    public function update(Request $request, Marque $marque)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mettre à jour la marque avec les nouvelles données
        $marque->update([
            'libelle' => $request->libelle,
        ]);
        
        LogJournalisation::create([
            "action"      => "Mise à jour de marque",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);

        // Retourner la réponse formatée avec MarqueResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'Marque modifiée avec succès', $marque);
    }

    // Supprimer une marque
    public function destroy(Marque $marque)
    {
        // Supprimer la marque
        $marque->isdeleted = true;
        $marque->save();

        LogJournalisation::create([
            "action"      => "Suppression de marque ID: " . $marque->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            "user_id"     => Auth::id(),
            "date_action" => now()
        ]);
        
        // Retourner la réponse formatée avec MarqueResource, indiquant que la suppression a réussi
        return new PostResource(true, 'Marque supprimée avec succès', null);
    }


}
