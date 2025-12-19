<?php

namespace App\Http\Controllers;

use App\Models\LogJournalisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Log; // Ajout pour la gestion des erreurs dans exportLogs

class LogJournalisationController extends Controller
{
    /**
     * Enregistrer un log
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|max:255',
            'ip_address' => 'nullable|string|max:50',
            'user_agent' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'date_action' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des logs',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer le nom de l'utilisateur authentifié si l'ID n'est pas fourni (ou si c'est une action interne)
        $user = $request->user();
        $userName = $user ? $user->name : null;

        $log = LogJournalisation::create([
            'action' => $request->action,
            // Utiliser l'IP du client si non fournie dans la requête
            'ip_address' => $request->ip_address ?? $request->ip(),
            'user_agent' => $request->user_agent ?? $request->header('User-Agent'),
            // Utiliser l'ID de l'utilisateur authentifié si non fourni
            'user_id' => $request->user_id ?? ($user ? $user->id : null),
            'user_name' => $userName, // Stocker le nom pour un accès plus rapide
            'date_action' => $request->date_action
                                ? Carbon::parse($request->date_action)
                                : now() // Utiliser now() au lieu de DB::raw('CURRENT_TIMESTAMP') pour plus de clarté
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Log enregistré avec succès',
            'data' => $log
        ], 201);
    }

    /**
     * Lister les logs (du plus récent au plus ancien) avec filtres.
     */
    public function index(Request $request)
    {
        // Base de la requête avec la jointure pour afficher le nom complet
        $query = LogJournalisation::orderBy('date_action', 'desc')
            ->leftJoin('users', 'log_journalisations.user_id', '=', 'users.id')
            ->select(
                'log_journalisations.*',
                // CONCAT(users.surname, ' ', users.name) pour les utilisateurs, sinon null
                DB::raw("CONCAT(users.surname, ' ', users.name) as user_name_full")
            );

        // 1. Filtrage par Action ou Adresse IP (recherche rapide)
        if ($request->filled('action')) {
            $searchTerm = '%' . $request->action . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('log_journalisations.action', 'like', $searchTerm)
                  ->orWhere('log_journalisations.ip_address', 'like', $searchTerm);
            });
        }

        // 2. Filtrage par ID Utilisateur
        if ($request->filled('user_id')) {
            $query->where('log_journalisations.user_id', $request->user_id);
        }

        // 3. Filtrage par Période (Date de début)
        if ($request->filled('date_debut')) {
            // S'assurer que l'heure est minuit (début du jour)
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $query->where('date_action', '>=', $dateDebut);
        }

        // 4. Filtrage par Période (Date de fin)
        if ($request->filled('date_fin')) {
            // S'assurer que l'heure est 23:59:59 (fin du jour)
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->where('date_action', '<=', $dateFin);
        }

        // Exécution de la requête
        $logs = $query->get();

        return response()->json([
            'success' => true,
            'total' => $logs->count(),
            'data' => $logs
        ]);
    }

    /**
     * Exporter les logs (avec les mêmes filtres que index) pour l'impression/export.
     * Génère un PDF et le stream.
     */
    public function exportLogs(Request $request)
    {
        // 1. Récupération et filtrage des données (identique à la méthode index)
        $query = LogJournalisation::orderBy('date_action', 'desc')
            ->leftJoin('users', 'log_journalisations.user_id', '=', 'users.id')
            ->select(
                'log_journalisations.*',
                DB::raw("CONCAT(users.surname, ' ', users.name) as user_name_full")
            );

        // Applique les filtres
        if ($request->filled('action')) {
            $searchTerm = '%' . $request->action . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('log_journalisations.action', 'like', $searchTerm)
                  ->orWhere('log_journalisations.ip_address', 'like', $searchTerm);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('log_journalisations.user_id', $request->input('user_id'));
        }

        if ($request->filled('date_debut')) {
            // Utilisation de Carbon pour s'assurer d'inclure les logs de la première seconde du jour
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $query->where('log_journalisations.date_action', '>=', $dateDebut);
        }

        if ($request->filled('date_fin')) {
            // Utilisation de Carbon pour s'assurer d'inclure les logs jusqu'à la dernière seconde du jour
            $dateFin = Carbon::parse($request->date_fin)->endOfDay();
            $query->where('log_journalisations.date_action', '<=', $dateFin);
        }

        // Exécution de la requête
        $logs = $query->get();

        // 2. Préparation des données pour la vue Blade
        $data = [
            'logs' => $logs,
            'filters' => $request->all(),
            'current_date' => Carbon::now()->format('d/m/Y H:i:s'),
        ];

        // 3. Génération et renvoi du PDF
        try {
            // Assurez-vous que la vue 'pdf.logs_journalisation' existe
            $pdf = PDF::loadView('pdf.logs_journalisation', $data);

            // Renvoie le PDF en tant que réponse binaire (stream)
            return $pdf->stream('journalisation_actions_' . Carbon::now()->format('Ymd_His') . '.pdf');
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du PDF de log : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la génération du PDF. Consultez les logs du serveur pour plus de détails.'], 500);
        }
    }

    /**
     * Voir un seul log
     */
    public function show($id)
    {
        $log = LogJournalisation::find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log
        ]);
    }

    /**
     * Supprimer un log
     */
    public function destroy($id)
    {
        $log = LogJournalisation::find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log non trouvé'
            ], 404);
        }

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log supprimé avec succès'
        ]);
    }
}
