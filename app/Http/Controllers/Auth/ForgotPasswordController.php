<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOTP;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class ForgotPasswordController extends Controller
{
    //  1. Envoyer un OTP au mail de l'utilisateur
    public function sendOTP(Request $request)
    {
        // 1. Validation
        $validationResult = Validator::make($request->all(), ['email' => 'required|email|exists:users,email']);

        if ($validationResult->fails()) {
            $email = $request->email ?? 'Non fourni';
            // ❌ LOG → Échec de validation (l'email n'existe pas ou format incorrect)
            LogJournalisation::create([
                'action'     => "Échec: Tentative d'envoi d'OTP à l'email '{$email}' (Email invalide ou inexistant).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => null,
                'date_action'=> now(),
            ]);
            return response()->json($validationResult->errors(), 422);
        }

        // L'utilisateur existe d'après la validation
        $user = User::where('email', $request->email)->first();
        $otp = rand(10000, 99999); // Génère un code OTP à 5 chiffres

        try {
            // 2. Sauvegarde l’OTP dans la base de données
            $user->otp_code = $otp;
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();

            // 3. Envoi de l'OTP par mail
            Mail::to($user->email)->send(new ResetPasswordOTP($otp));

            // ✅ LOG → Succès de l'envoi d'OTP
            LogJournalisation::create([
                'action'     => "Succès: Envoi du code OTP pour réinitialisation de mot de passe à l'utilisateur ID {$user->id} [Email: {$user->email}].",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                // On utilise l'ID de l'utilisateur concerné.
                'user_id'    => $user->id, 
                'date_action'=> now(),
            ]);

            return response()->json(['message' => 'Un code OTP a été envoyé à votre email.']);

        } catch (\Exception $e) {
            // ❌ LOG → Échec de l'envoi ou de la sauvegarde (problème serveur/mail)
            \Log::error("Erreur lors de l'envoi de l'OTP à {$user->email}: " . $e->getMessage());
            
            LogJournalisation::create([
                'action'     => "Échec critique: Erreur serveur lors de l'envoi de l'OTP à l'utilisateur ID {$user->id} [Email: {$user->email}].",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $user->id,
                'date_action'=> now(),
            ]);
            
            return response()->json(['message' => 'Erreur serveur lors de l\'envoi du code. Veuillez réessayer.'], 500);
        }
    }

    //  2. Vérifier si l’OTP est valide
    public function verifyOTP(Request $request)
    {
        // 1. Validation
        $validationResult = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|numeric'
        ]);

        $email = $request->email ?? 'Non fourni';

        if ($validationResult->fails()) {
            // ❌ LOG → Échec de validation (tentative de vérification OTP)
            LogJournalisation::create([
                'action'     => "Échec: Tentative de vérification d'OTP pour l'email '{$email}' (Validation des données échouée).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => null,
                'date_action'=> now(),
            ]);
            return response()->json($validationResult->errors(), 422);
        }

        // 2. Recherche et vérification de l'utilisateur, du code et de l'expiration
        $user = User::where('email', $email)
                        ->where('otp_code', $request->otp_code)
                        ->where('otp_expires_at', '>', now())
                        ->first();

        // 3. Si le code OTP est invalide ou expiré
        if (!$user) {
            // ❌ LOG → Échec de la vérification du code OTP
            LogJournalisation::create([
                'action'     => "Échec: Tentative de vérification d'OTP pour l'email '{$email}' (Code OTP invalide ou expiré).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => null,
                'date_action'=> now(),
            ]);
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 400);
        }

        // 4. Succès
        // ✅ LOG → Succès de la vérification du code OTP
        LogJournalisation::create([
            'action'     => "Succès: Vérification du code OTP réussie pour l'utilisateur ID {$user->id} [Email: {$user->email}]. Accès à la réinitialisation accordé.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            // On utilise l'ID de l'utilisateur concerné.
            'user_id'    => $user->id, 
            'date_action'=> now(),
        ]);

        return response()->json(['message' => 'Code OTP valide. Vous pouvez maintenant définir un nouveau mot de passe.']);
    }

    //  3. Réinitialiser le mot de passe
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|numeric',
            'password' => 'required|string|min:8|confirmed'
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            // ❌ LOG → Échec de validation (tentative de réinitialisation)
            LogJournalisation::create([
                'action'     => "Échec: Tentative de réinitialisation de mot de passe pour l'email '{$request->email}' (Validation des données échouée).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                // user_id est null ici car l'utilisateur n'est pas encore authentifié/identifié de manière fiable.
                'user_id'    => null,
                'date_action'=> now(),
            ]);
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)
                        ->where('otp_code', $request->otp_code)
                        ->where('otp_expires_at', '>', now())
                        ->first();
        
        // Si le code OTP est invalide ou expiré
        if (!$user) {
            // ❌ LOG → Échec du code OTP
            $email = $request->email ?? 'Non fourni';
            LogJournalisation::create([
                'action'     => "Échec: Tentative de réinitialisation de mot de passe pour l'email '{$email}' (Code OTP invalide ou expiré).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => null,
                'date_action'=> now(),
            ]);
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 400);
        }

        // Mise à jour du mot de passe
        $user->password = Hash::make($request->password);
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        // Révoquer tous les anciens tokens
        $user->tokens()->delete();

        // ✅ LOG → Succès de la réinitialisation
        LogJournalisation::create([
            'action'     => "Succès: Réinitialisation du mot de passe de l'utilisateur ID {$user->id} [Email: {$user->email}] via code OTP.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            // On utilise l'ID de l'utilisateur dont le mot de passe a été changé pour le log
            'user_id'    => $user->id, 
            'date_action'=> now(),
        ]);

        return response()->json(['message' => 'Votre mot de passe a été mis à jour avec succès. Vous devez vous reconnecter.']);
    }

}
