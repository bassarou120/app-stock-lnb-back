<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategorieSortieTicket;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class CategorieSortieTicketController extends Controller
{
     // Afficher la liste des communes
    public function index(Request $request)
    {
        $categorieSortieTicket = CategorieSortieTicket::latest()->where('isdeleted', false)->paginate(1000);

        // 📝 LOG → Consultation de la liste des CategorieSortieTicket
        LogJournalisation::create([
            'action'     => 'Consultation de la liste des CategorieSortieTicket',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Retourner la réponse formatée avec PostResource
        return new PostResource(true, 'Liste des CategorieSortieTicket', $categorieSortieTicket);
    }

    // Créer une nouvelle commune
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

        // Créer une nouvelle commune avec les données valides
        $categorieSortieTicket = CategorieSortieTicket::create([
            'libelle' => $request->libelle,
        ]);

        // 📝 LOG → Création d'une CategorieSortieTicket
        LogJournalisation::create([
            'action'     => 'Création de la CategorieSortieTicket ID ' . $categorieSortieTicket->id . ' (Libellé: ' . $categorieSortieTicket->libelle . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la création a réussi
        return new PostResource(true, 'Commune créée avec succès !', $categorieSortieTicket);
    }

    // Mettre à jour une commune existante
    public function update(Request $request, CategorieSortieTicket $categorieSortieTicket)
    {
        // Définir les règles de validation pour les données envoyées
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255',
        ]);

        // Vérifier si la validation a échoué
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mettre à jour la commune avec les nouvelles données
        $categorieSortieTicket->update([
            'libelle' => $request->libelle,
        ]);

        // 📝 LOG → Mise à jour d'une CategorieSortieTicket
        LogJournalisation::create([
            'action'     => 'Modification de la CategorieSortieTicket ID ' . $categorieSortieTicket->id . ' (Libellé: ' . $categorieSortieTicket->libelle . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Retourner la réponse formatée avec PostResource, indiquant que la mise à jour a réussi
        return new PostResource(true, 'categorieSortieTicket modifiée avec succès', $categorieSortieTicket);
    }

    // Supprimer une categorieSortieTicket
    public function destroy(CategorieSortieTicket $categorieSortieTicket, Request $request)
    {
        // Supprimer la categorieSortieTicket
        $categorieSortieTicket->isdeleted = true;
        $categorieSortieTicket->save();
        // 📝 LOG → Suppression d'une CategorieSortieTicket
        LogJournalisation::create([
            'action'     => 'Suppression de la CategorieSortieTicket ID ' . $categorieSortieTicket->id . ' (Libellé: ' . $categorieSortieTicket->libelle . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);
        // Retourner la réponse formatée avec PostResource, indiquant que la suppression a réussi
        return new PostResource(true, 'categorieSortieTicket supprimée avec succès', null);
    }

}