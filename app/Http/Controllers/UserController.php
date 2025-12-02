<?php

namespace App\Http\Controllers;

use App\Mail\UserRegisteredMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User; // Assure-toi que ton modèle User est correctement importé
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Parametrage\Employe;
use Illuminate\Http\Request;
use App\Models\Role; // Si tu as besoin d'inclure le rôle
use Illuminate\Support\Facades\DB; // Ajout pour les transactions
use Illuminate\Support\Facades\Auth; // Ajout pour l'utilisateur connecté
use App\Models\LogJournalisation; // Ajout du modèle de journalisation

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $users = User::with('role')
            ->orderBy('created_at', 'desc')
            ->where('isdeleted', false)
            ->paginate(10);

            LogJournalisation::create([
                'action'     => "Consultation de la liste des utilisateurs",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegisterRequest $request)
    {
        // La validation est gérée par RegisterRequest.
        $validatedData = $request->validated();
        $generatedPassword = Str::random(10); // Mot de passe généré

        DB::beginTransaction();

        try {
            // On trouve l'employé pour récupérer ses informations
            $employe = Employe::findOrFail($validatedData['employe_id']);

            // Création de l'utilisateur
            $user = User::create([
                'name' => $employe->nom,
                'surname' => $employe->prenom ?? null,
                'email' => $employe->email,
                'phone' => $employe->telephone,
                'password' => Hash::make($generatedPassword), // Hashage du mot de passe généré
                'role_id' => $validatedData['role_id'],
                'active' => $validatedData['active'] ?? true,
                'employe_id' => $employe->id,
            ]);

            // Envoi de l'e-mail avec le mot de passe en clair (Doit se faire après le commit ou géré séparément)
            // Pour des raisons de robustesse, on garde l'envoi de mail après le commit ou géré par queue.
            // Ici, nous le faisons après la création réussie.

            DB::commit();

            try {
                Mail::to($user->email)->send(new UserRegisteredMail($user, $generatedPassword));
                $emailStatus = 'E-mail envoyé.';
            } catch (\Exception $e) {
                $emailStatus = 'Échec de l\'envoi de l\'e-mail: ' . $e->getMessage();
                LogJournalisation::create([
                    'action'     => 'Création utilisateur échouée',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => null,
                    'date_action'=> now(),
                    'details'    => "User , Email: {$user->email}"
                ]);
            }

            // 📝 LOG → Création réussie
            LogJournalisation::create([
                'action'     => 'Création utilisateur réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "User ID: {$user->id}, Email: {$user->email}, Rôle: {$user->role_id}. {$emailStatus}"
            ]);

            return response()->json($user->load('role'), 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Création échouée (exception)
            $employeId = $validatedData['employe_id'] ?? 'N/A';
            LogJournalisation::create([
                'action'     => 'Création utilisateur échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "Employé ID: {$employeId}. Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de la création de l\'utilisateur.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Charge l'utilisateur par son ID (qui est un UUID ici)
        // Eager load les relations 'role' et 'employe'
        $user = User::with(['role', 'employe'])->find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $oldUserData = $user->toJson();

        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|nullable|string|max:255',
                'surname' => 'sometimes|nullable|string|max:255',
                'email' => 'sometimes|nullable|string|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->id,
                'sexe' => 'sometimes|nullable|in:Masculin,Féminin',
                'password' => 'sometimes|nullable|string|min:8', // Mot de passe optionnel
                'role_id' => 'sometimes|required|exists:roles,id',
                'active' => 'sometimes|boolean',
            ]);

            if (isset($validatedData['password']) && !empty($validatedData['password'])) {
                $validatedData['password'] = bcrypt($validatedData['password']);
                $passwordChanged = true;
            } else {
                unset($validatedData['password']);
                $passwordChanged = false;
            }

            DB::beginTransaction();

            $user->update($validatedData);

            DB::commit();

            // 📝 LOG → Mise à jour réussie
            LogJournalisation::create([
                'action'     => 'Mise à jour utilisateur réussie',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "User ID: {$user->id}, Email: {$user->email}. PWD changé: " . ($passwordChanged ? 'Oui' : 'Non') . ". Anciennes données: {$oldUserData}."
            ]);

            return response()->json($user->load('role'));

        } catch (ValidationException $e) {
            // 📝 LOG → Échec de validation
            LogJournalisation::create([
                'action'     => 'Échec validation (mise à jour utilisateur)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "User ID: {$user->id}. Erreurs: " . json_encode($e->errors())
            ]);
            throw $e; // Renvoyer l'exception de validation après le log
        } catch (\Exception $e) {
            DB::rollBack();
            // 📝 LOG → Mise à jour échouée (exception)
            LogJournalisation::create([
                'action'     => 'Mise à jour utilisateur échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "User ID: {$user->id}. Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de la mise à jour de l\'utilisateur.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request) // Ajout de Request pour la journalisation
    {
        $user = User::find($id);

        if (!$user) {
            // 📝 LOG → Échec suppression (non trouvé)
            LogJournalisation::create([
                'action'     => 'Échec suppression utilisateur (non trouvé)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => "User ID: {$id} introuvable."
            ]);
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        DB::beginTransaction();
        $detailsLog = "User ID: {$user->id}, Email: {$user->email}";

        try {
            // Suppression logique (Soft Delete)
            $user->isdeleted = true;
            $user->save();

            DB::commit();

            // 📝 LOG → Suppression réussie
            LogJournalisation::create([
                'action'     => 'Suppression utilisateur réussie (soft delete)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $detailsLog
            ]);

            return response()->json(['message' => 'Utilisateur supprimé avec succès'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            // 📝 LOG → Suppression échouée (exception)
            LogJournalisation::create([
                'action'     => 'Suppression utilisateur échouée (exception)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
                'details'    => $detailsLog . ". Erreur: " . $e->getMessage()
            ]);

            return response()->json(['message' => 'Erreur lors de la suppression de l\'utilisateur.'], 500);
        }
    }
}
