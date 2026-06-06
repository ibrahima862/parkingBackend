<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\User;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use function PHPUnit\Framework\isEmpty;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        }

        return response()->json(['message' => 'Identifiants invalides'], 401);
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:client,proprietaireparking',
            'rectoCIN' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'versoCIN' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        ];

        if ($request->role === 'proprietaireparking') {
            $rules['nomParking'] = 'required|string|max:255';
            $rules['quartier'] = 'required|string|max:255';
            $rules['capacite'] = 'required|integer|min:1';
            $rules['longitude'] = 'required|numeric';
            $rules['latitude'] = 'required|numeric';
            $rules['rectoCIN'] = 'required|image|mimes:jpeg,jpg,png|max:5120';
            $rules['versoCIN'] = 'required|image|mimes:jpeg,jpg,png|max:5120';
        }

        $validatedData = $request->validate($rules);
    
        return DB::transaction(function () use ($validatedData, $request) {

            // Initialiser les URLs à null
            $rectoUrl = null;
            $versoUrl = null;

            // --- GESTION CLOUDINARY ---
            if ($request->role === 'proprietaireparking') {
                Configuration::instance([
                    'cloud' => [
                        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                        'api_key' => env('CLOUDINARY_API_KEY'),
                        'api_secret' => env('CLOUDINARY_API_SECRET')
                    ],
                ]);

                $upload = new UploadApi();

                if ($request->hasFile('rectoCIN')) {
                    $rectoUrl = $upload->upload($request->file('rectoCIN')->getRealPath(), [
                        'folder' => 'senovapark/cin',
                    ])['secure_url'];
                }

                if ($request->hasFile('versoCIN')) {
                    $versoUrl = $upload->upload($request->file('versoCIN')->getRealPath(), [
                        'folder' => 'senovapark/cin',
                    ])['secure_url'];
                }
            }
            // Création de l'utilisateur (Simplifié : un seul appel)
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'telephone' => $validatedData['telephone'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
                'is_approved' => ($validatedData['role'] === 'client'),
                'rectoCIN' => $rectoUrl,
                'versoCIN' => $versoUrl,
            ]);

            if ($user->role === 'proprietaireparking') {
                Parking::create([
                    'nom' => $validatedData['nomParking'],
                    'quartier' => $validatedData['quartier'],
                    'capacite' => $validatedData['capacite'],
                    'description' => $request->description ?? null,
                    'longitude' => $validatedData['longitude'],
                    'latitude' => $validatedData['latitude'],
                    'proprietaire_id' => $user->id,
                    'statut' => 'en_attente',
                    'pays' => 'Sénégal',
                    'departement' => 'Dakar',
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 201);
        });
    }

    public function convertToPartner(Request $request)
    {
        $user = $request->user();

        // 1. Validation des infos parking uniquement
        $validatedData = $request->validate([
            'nomParking' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'capacite' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validatedData, $user) {
            // 2. Mise à jour du rôle de l'utilisateur
            // On le repasse en 'is_approved = false' pour que l'admin valide le nouveau statut
            $user->update([
                'role' => 'proprietaireparking',
                'is_approved' => false,
            ]);

            // 3. Création du parking associé
            $parking = Parking::create([
                'nom' => $validatedData['nomParking'],
                'quartier' => $validatedData['quartier'],
                'capacite' => $validatedData['capacite'],
                'description' => $validatedData['description'] ?? null,
                'proprietaire_id' => $user->id,
                'statut' => 'en_attente',
                'pays' => 'Sénégal',
                'departement' => 'Dakar',
                'latitude' => null,
                'longitude' => null,
            ]);

            return response()->json([
                'message' => 'Demande de partenariat enregistrée. Votre compte est en attente de validation.',
                'user' => $user->fresh(), // Renvoie l'utilisateur mis à jour
            ], 200);
        });
    }
    public function logout(Request $request)
    {
        // Supprime le token actuel de l'utilisateur
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}