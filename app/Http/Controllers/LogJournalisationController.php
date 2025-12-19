<?php

namespace App\Http\Controllers;

use App\Models\LogJournalisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

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

        // récupérer l'utilisateur connecté
        $userId = $request->user_id ?? Auth::id();

        $log = LogJournalisation::create([
            'action' => $request->action,
            'ip_address' => $request->ip_address ?? $request->ip(),
            'user_agent' => $request->user_agent ?? $request->header('User-Agent'),
            'user_id' => $request->user_id,
            'user_name'   => $request->user()->name,
            'date_action' => $request->date_action
                        ? $request->date_action
                        : DB::raw('CURRENT_TIMESTAMP')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Log enregistré avec succès',
            'data' => $log
        ], 201);
    }

    /**
     * Lister les logs (du plus récent au plus ancien)
     */
    public function index()
    {
        // Concaténation des colonnes 'name' et 'surname' pour former le nom complet.
        // On suppose que votre SGBD utilise CONCAT (MySQL/PostgreSQL).
        // Si vous utilisez SQL Server ou autre, l'opérateur de concaténation pourrait être différent.
        $logs = LogJournalisation::orderBy('date_action', 'desc')
            ->leftJoin('users', 'log_journalisations.user_id', '=', 'users.id')
            ->select(
                'log_journalisations.*',
                DB::raw("CONCAT(users.surname, ' ', users.name) as user_name_full") // Nom complet
            )
            ->get();

        return response()->json([
            'success' => true,
            'total' => $logs->count(),
            'data' => $logs
        ]);
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