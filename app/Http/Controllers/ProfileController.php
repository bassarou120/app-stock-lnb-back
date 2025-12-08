<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\LogJournalisation;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordUpdatedMail;


class ProfileController extends Controller
{
    //
/*     public function updateProfile(Request $request)
    {
        // Récupération de l'utilisateur connecté
        $user = Auth::user();

        // Validation des champs
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'phone' => 'nullable|string|max:20',
        ]);

        // Mise à jour
        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user
        ], 200);
    } */

    public function getProfile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('role', 'employe'),
        ]);
    }


    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            //'surname' => 'nullable|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|string|min:6',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        DB::beginTransaction();

        try {

            $newPassword = null;

            // Changement du mot de passe
            if ($request->filled('current_password') && $request->filled('new_password')) {

                if (!Hash::check($request->current_password, $user->password)) {
                    throw ValidationException::withMessages([
                        'current_password' => ['Le mot de passe actuel est incorrect.']
                    ]);
                }

                $newPassword = $request->new_password;
                $validatedData['password'] = Hash::make($newPassword);

                LogJournalisation::create([
                    'action' => 'Changement de mot de passe',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'date_action' => now(),
                ]);
            }

            unset($validatedData['current_password'], $validatedData['new_password']);

            $user->update($validatedData);

            DB::commit();

            // Envoi du mail de mise à jour du mot de passe
            if ($newPassword) {
                try {
                    Mail::to($user->email)->send(new PasswordUpdatedMail($user, $newPassword));
                } catch (\Exception $e) {
                    LogJournalisation::create([
                        'action' => 'Échec envoi mail changement mot de passe',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'date_action' => now(),
                    ]);
                }
            }

            LogJournalisation::create([
                'action' => 'Mise à jour du profil',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'date_action' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
                'data' => $user->fresh()->load('role', 'employe')
            ], 200);

        }catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur updateProfile: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil.',
                'error' => $e->getMessage() // pour debug côté Angular
            ], 500);
        }


    }


}
