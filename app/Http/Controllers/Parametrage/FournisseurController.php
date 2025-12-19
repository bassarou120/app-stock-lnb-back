<?php

namespace App\Http\Controllers\Parametrage;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Fournisseur;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class FournisseurController extends Controller
{
    // Afficher la liste des fournisseurs
    public function index(Request $request)
    {
        $fournisseurs = Fournisseur::latest()->where('isdeleted', false)->paginate(100);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des fournisseurs",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des fournisseurs', $fournisseurs);
    }

    // Créer un nouveau fournisseur
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $fournisseur = Fournisseur::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
        ]);
        LogJournalisation::create([
            "action"      => "Création de fournisseur",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Fournisseur créé avec succès', $fournisseur);
    }

    // Mettre à jour un fournisseur existant
    public function update(Request $request, Fournisseur $fournisseur)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $fournisseur->update([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
        ]);

        LogJournalisation::create([
            "action"      => "Mise à jour de fournisseur ID: " . $fournisseur->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Fournisseur mis à jour avec succès', $fournisseur);
    }

    // Supprimer un fournisseur
    public function destroy(Fournisseur $fournisseur, Request $request)
    {
        $fournisseur->isdeleted = true;
        $fournisseur->save();
        LogJournalisation::create([
            "action"      => "Suppression de fournisseur ID: " . $fournisseur->id,
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Fournisseur supprimé avec succès', null);
    }

    public function imprimer(Request $request)
    {
        $fournisseurs = Fournisseur::all()->where('isdeleted', false);

        $pdf = Pdf::loadView('pdf.fournisseurs', compact('fournisseurs'));

        LogJournalisation::create([
            "action"      => "Impression de la liste des fournisseurs",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return $pdf->download('liste_fournisseurs.pdf');
    }


}
