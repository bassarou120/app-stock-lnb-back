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

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $users = User::with('role')
            ->orderBy('created_at', 'desc')
            ->where('isdeleted', false)
            ->paginate(10);
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegisterRequest $request)
    {
        // La validation est maintenant entièrement gérée par RegisterRequest.
        // On récupère les données déjà validées.
        $validatedData = $request->validated();

        // On trouve l'employé pour récupérer ses informations
        $employe = Employe::findOrFail($validatedData['employe_id']);

        // Génération automatique du mot de passe
        $generatedPassword = Str::random(10); // Génère une chaîne aléatoire de 10 caractères

        // Création de l'utilisateur avec les données de l'employé et les données validées
        $user = User::create([
            'name' => $employe->nom,
            'surname' => $employe->prenom ?? null, // Utilisez 'prenom' si votre modèle Employe l'a, sinon null
            'email' => $employe->email,
            'phone' => $employe->telephone,
            // 'sexe' => $validatedData['sexe'], // Cette valeur vient de la validation de RegisterRequest
            'password' => Hash::make($generatedPassword), // Hashage du mot de passe généré
            'role_id' => $validatedData['role_id'], // Cette valeur vient de la validation de RegisterRequest
            'active' => $validatedData['active'] ?? true, // Utilise la valeur validée, ou true par défaut
            'employe_id' => $employe->id,
            // 'photo' => ..., // Logique pour la photo si elle est gérée
        ]);

        // Envoi de l'e-mail avec le mot de passe en clair
        Mail::to($user->email)->send(new UserRegisteredMail($user, $generatedPassword));

        // Retourne la réponse JSON
        return response()->json($user->load('role'), 201);
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
        } else {
            unset($validatedData['password']); // Ne pas mettre à jour le mot de passe s'il est vide
        }

        $user->update($validatedData);

        return response()->json($user->load('role'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id); // Trouve l'utilisateur par son ID (UUID ou ID auto-incrémenté)

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        try {
            $user->isdeleted = true;
            $user->save();
            return response()->json(['message' => 'Utilisateur supprimé avec succès'], 200);
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage (optionnel mais recommandé)
            \Log::error('Erreur lors de la suppression de l\'utilisateur ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la suppression de l\'utilisateur.'], 500);
        }
    }
}
