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

/**
 * @OA\Tag(
 *     name="Demande Immobilisation",
 *     description="Gestion des demandes d'immobilisation"
 * )
 */


class DemandeImmoController extends Controller
{
    // LISTE DES DEMANDES

    /**
     * @OA\Get(
     *     path="/api/demande-immo",
     *     summary="Liste des demandes d'immobilisation",
     *     description="Retourne la liste paginée des demandes d'immobilisation.",
     *     tags={"Demande Immobilisation"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des demandes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des demandes"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $demandes = DemandeImmo::where('isdeleted', false)
            ->with(['employe', 'traiteur', 'immobilisation', 'groupeTypeImmo', 'exercice'])
            ->latest()
            ->paginate(1000);

        return new PostResource(true, 'Liste des demandes', $demandes);
    }



    //LISTE DES GROUPES TYPES IMMO

    /**
     * @OA\Get(
     *     path="/api/demande-imo/groupeTypeImmo",
     *     summary="Liste des groupes de type immobilisation",
     *     description="Retourne la liste des groupes de type immobilisation.",
     *     tags={"Demande Immobilisation"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des groupes de type immobilisation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des groupes de type immo"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */

    // Afficher la liste des groupes de type immo
    public function groupeTypeImmo()
    {
        $groupe_type_immos = GroupeTypeImmo::latest()->where('isdeleted', false)->paginate(10000);
        return new PostResource(true, 'Liste des groupes de type immmo', $groupe_type_immos);
    }

    // public function show() {

    //     $groupe_type_immos = GroupeTypeImmo::latest()->where('isdeleted', false)->paginate(10000);
    //     return new PostResource(true, 'Liste des groupes de type immmo', $groupe_type_immos);

    // }

    // CREER UNE DEMANDE

    /**
     * @OA\Post(
     *     path="/api/demande-immo",
     *     summary="Créer une demande d'immobilisation",
     *     description="Permet de créer une nouvelle demande d'immobilisation.",
     *     tags={"Demande Immobilisation"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations nécessaires pour créer une demande d'immobilisation",
     *         @OA\JsonContent(
     *             required={"date_demande"},
     *
     *             @OA\Property(
     *                 property="email_personnel",
     *                 type="string",
     *                 format="email",
     *                 example="employe@example.com",
     *                 description="Email de l'employé qui fait la demande"
     *             ),
     *
     *             @OA\Property(
     *                 property="date_demande",
     *                 type="string",
     *                 format="date",
     *                 example="2026-03-11",
     *                 description="Date de la demande d'immobilisation"
     *             ),
     *
     *             @OA\Property(
     *                 property="libelle_groupe_type_immo",
     *                 type="string",
     *                 example="Matériel informatique",
     *                 description="Libellé du groupe de type d'immobilisation"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Demande d'immobilisation créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Demande créée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Aucun exercice ouvert"
     *     )
     * )
     */
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

            if ($status === 'VALIDE') {
                //$this->notifierMRequestDemandeImmo($demande, 'ACCEPTEE');
                $url = $this->notifierMRequestDemandeImmo($demande, 'ACCEPTEE');
                $demande->mRequest = $url;
                $demande->save();
            }

            if ($status === 'REJETE') {
                //$this->notifierMRequestDemandeImmo($demande, 'REJETEE');
                $url = $this->notifierMRequestDemandeImmo($demande, 'REJETEE');
                $demande->mRequest = $url;
                $demande->save();
            }

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
            'date_action' => now(),
        ]);

        return $pdf->download(
            'Fiche_Demande_Immo_' .
                $demande->id .
                '_' .
                $numeroFiche .
                '.pdf'
        );
    }


    protected function notifierMRequestDemandeImmo(DemandeImmo $demande, string $decision)
    {
        $reference = $demande->mRequest;
        $baseUrl = rtrim(config('services.m_request.base_url'), '/');

        if (empty($reference) || empty($baseUrl)) {

            logger()->warning('URL M_REQUEST non construite pour DemandeImmo', [
                'reference' => $reference,
                'baseUrl'   => $baseUrl,
                'demande_id'=> $demande->id
            ]);

            return null;
        }

        $url = "{$baseUrl}/api/demandes-immo/" . urlencode($reference) . "/traiter";

        // Log URL construite
        logger()->info("URL M_REQUEST DemandeImmo : {$url}");

        try {

            $response = Http::timeout(5)->post($url, [
                'statut'          => 'Traitée',
                'decision'        => $decision,
                'date_traitement' => now()->toDateTimeString(),
            ]);

            if ($response->failed()) {

                logger()->error('❌ Échec appel M_REQUEST DemandeImmo', [
                    'url' => $url,
                    'payload' => [
                        'statut'   => 'Traitée',
                        'decision' => $decision
                    ],
                    'response' => $response->body()
                ]);
            }

        } catch (\Throwable $e) {

            logger()->error('❌ Exception appel M_REQUEST DemandeImmo', [
                'url'   => $url,
                'error' => $e->getMessage()
            ]);
        }

        return $url;
    }




}
