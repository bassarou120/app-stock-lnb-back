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
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp = rand(10000, 99999); // Génère un code OTP à 5 chiffres

        // Sauvegarde l’OTP dans la base de données (avec expiration de 10 min)
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Envoi de l'OTP par mail
        Mail::to($user->email)->send(new ResetPasswordOTP($otp));

        return response()->json(['message' => 'Un code OTP a été envoyé à votre email.']);
    }

    //  2. Vérifier si l’OTP est valide
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|numeric'
        ]);

        $user = User::where('email', $request->email)
                    ->where('otp_code', $request->otp_code)
                    ->where('otp_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 400);
        }

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
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)
                    ->where('otp_code', $request->otp_code)
                    ->where('otp_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 400);
        }

        // Mise à jour du mot de passe
        $user->password = Hash::make($request->password);
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        // Révoquer tous les anciens tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Votre mot de passe a été mis à jour avec succès. Vous devez vous reconnecter.']);
    }

}
