<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordContoller extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // On ne dit pas si l'email n'existe pas (sécurité)
        if ($user) {
            // 1. Créer un token unique
            $token = Str::random(64);

            // 2. Stocker le token en base (table password_reset_tokens)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token), // On hash le token par sécurité
                    'created_at' => Carbon::now()
                ]
            );

             Mail::to($user->email)->send(new ResetPasswordMail($token, $user->email));
        }

        return response()->json(['message' => 'Si votre adresse est correcte, vous recevrez un lien sous peu.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        // 1. Récupérer le token en base
        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // 2. Vérifier si le token existe et n'est pas expiré (ex: 60 min)
        if (!$resetData || !Hash::check($request->token, $resetData->token)) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 400);
        }

        if (Carbon::parse($resetData->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Lien expiré.'], 400);
        }

        // 3. Mettre à jour le mot de passe de l'utilisateur
        $user = User::where('email', $request->email)->first();
        $user->update([ 
            'password' => Hash::make($request->password)
        ]);

        // 4. Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Votre mot de passe a été réinitialisé avec succès.']);
    }


}
