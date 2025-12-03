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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des logs',
                'errors' => $validator->errors()
            ], 422);
        }

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
        $logs = LogJournalisation::orderBy('date_action', 'desc')->get();

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
