<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exercice;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\GroupeTypeImmo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\LogJournalisation;
use App\Models\DemandeImmo;
use App\Http\Resources\PostResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PDF;


class DemandeImmoController extends Controller
{
    // LISTE DES DEMANDES
    public function index()
    {
        $demandes = DemandeImmo::where('isdeleted', false)
            ->with(['employe', 'traiteur', 'immobilisation', 'groupeTypeImmo', 'exercice'])
            ->latest()
            ->paginate(1000);

        return new PostResource(true, 'Liste des demandes', $demandes);
    }


    // CREER UNE DEMANDE
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "email_personnel" => "nullable|email|exists:employes,email",
            "date_demande" => "required|date",
            "libelle_groupe_type_immo" => "nullable|string",
        ]);

        if ($validator->fails()) {

            LogJournalisation::create([
                'action'      => 'Échec: Tentative de création de demande de Sortie de Stock Multiple (Validation échouée)',
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->header('User-Agent'),
                'user_id'     => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action' => now(),
            ]);

            return response()->json($validator->errors(), 422);
        }

        // récupération de l'employé via email
        $id_employe = null;

        if ($request->filled('email_personnel')) {

            $personnel = Employe::where('email', $request->email_personnel)->first();

            if ($personnel) {
                $id_employe = $personnel->id;
            }
        }

        // récupération du groupe type immo
        $id_groupe_type_immo = null;

        if ($request->filled('libelle_groupe_type_immo')) {

            $libelle = strtolower($request->libelle_groupe_type_immo);

            $groupeTypeImmo = GroupeTypeImmo::whereRaw('LOWER(libelle) = ?', [$libelle])->first();

            if ($groupeTypeImmo) {
                $id_groupe_type_immo = $groupeTypeImmo->id;
            }
        }

        // exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();

        if (!$exerciceOuvert) {
            return response()->json([
                "message" => "Aucun exercice ouvert trouvé"
            ], 400);
        }

        $code_demande = 'DI-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));

        // création demande
        $demande = DemandeImmo::create([
            'ref_demande' => $code_demande,
            'id_employe' => $id_employe,
            'id_traiteur' => null,
            'id_groupe_type_immo' => $id_groupe_type_immo,
            'id_immo' => null,
            'date_demande' => $request->date_demande,
            'status' => 'EN_ATTENTE',
            'url_fiche' => null,
            'id_exercice' => $exerciceOuvert->id,
            'mRequest' => null
        ]);

        return new PostResource(true, 'Demande créée avec succès', $demande);
    }



    public function changerStatus(Request $request, $idDemande)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:EN_ATTENTE,VALIDE,REJETE,CLOTUREE',
            'fichier' => 'nullable|file|max:10240', // max 10MB
            'bureau_id' => 'nullable|exists:bureaus,id',
            'employe_id' => 'nullable|exists:employes,id',
            'immo_id' => 'nullable|exists:immobilisations,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $demande = DemandeImmo::find($idDemande);
        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable'], 404);
        }

        $status = $request->status;

        DB::beginTransaction();
        try {

            switch ($status) {
                case 'EN_ATTENTE':
                    $demande->status = 'EN_ATTENTE';
                    break;

                case 'VALIDE':
                    $demande->status = 'VALIDE';

                    // On déclenche le transfert seulement si toutes les infos sont présentes
                    if (!$request->filled(['immo_id', 'bureau_id'])) {
                        return response()->json([
                            'message' => 'Pour valider, vous devez fournir l’ID de l’immobilisation et le bureau.'
                        ], 422);
                    }

                    // On enregistre l'immo et le traiteur (celui qui valide) dans la demande
                    $demande->id_immo = $request->immo_id;
                    // $demande->id_traiteur = $request->user()->id;

                    // Préparer un nouveau Request pour le TransfertController
                    $transfertRequest = new Request([
                        'immo_id' => $request->immo_id,
                        'bureau_id' => $request->bureau_id,
                        'employe_id' => $request->employe_id,
                        'date_mouvement' => now(),
                        'observation' => 'Transfert depuis validation de la demande DI-' . $demande->id,
                    ]);

                    // Injection de l'utilisateur courant pour les logs et id_traiteur
                    $transfertRequest->setUserResolver(function () use ($request) {
                        return $request->user();
                    });

                    // Appel du controller de transfert
                    app(\App\Http\Controllers\TransfertController::class)->store($transfertRequest);

                    break;

                case 'REJETE':
                    $demande->status = 'REJETE';
                    break;

                case 'CLOTUREE':
                    $demande->status = 'CLOTUREE';

                    if (!$request->hasFile('fichier')) {
                        return response()->json([
                            'message' => 'Vous devez fournir un fichier pour clôturer la demande.'
                        ], 422);
                    }

                    $file = $request->file('fichier');
                    $filename = 'demande_' . $demande->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                    // $path = $file->storeAs('public/demandes-immo', $filename);
                    $path = $file->storeAs('demandes-immo', $filename, 'public');

                    // $demande->url_fiche = asset(str_replace('public/', 'storage/', $path));
                    $demande->url_fiche = asset('storage/' . $path);
                    $demande->save();
                    break;
            }

            $demande->save();

            // 📝 Journalisation
            LogJournalisation::create([
                'action'     => "Changement de status demande DI-{$demande->id} à {$status}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'  => $request->user()->name,
                'date_action' => now(),
            ]);

            DB::commit();

            return new PostResource(true, "Status mis à jour avec succès", $demande);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors du changement de status: ' . $e->getMessage()], 500);
        }
    }


    public function genererFicheDemandeImmo($id, Request $request)
{
    $demande = DemandeImmo::with('immobilisation', 'employe')
        ->find($id);

    if (!$demande) {
        abort(404, "La demande d'immobilisation n'existe pas.");
    }

    // Générer numéro de fiche
    $numeroFiche =  $demande->ref_demande;

    $demande->update([
        'ref_demande' => $numeroFiche
    ]);

    $demande->refresh();

    $authUser = Auth::user();

    $data = [
        'demande' => $demande,
        'authUser' => $authUser,
        'numeroFiche' => $numeroFiche
    ];

    $pdf = PDF::loadView('pdf.demande_immo', $data);

    LogJournalisation::create([
        'action'     => "Génération fiche demande immobilisation [ID: {$id}, Fiche: {$numeroFiche}]",
        'ip_address' => request()->ip(),
        'user_agent' => request()->header('User-Agent'),
        'user_id'    => $request->user()->id,
        'user_name'  => $request->user()->name,
        'date_action'=> now(),
    ]);

    return $pdf->download(
        'Fiche_Demande_Immo_' .
        $demande->id .
        '_' .
        $numeroFiche .
        '.pdf'
    );
}
}
