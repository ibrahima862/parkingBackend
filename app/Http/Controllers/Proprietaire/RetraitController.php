<?php


namespace App\Http\Controllers\Proprietaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Retrait;
use Illuminate\Support\Facades\Auth;

class RetraitController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. Validation des données entrantes
        $request->validate([
            'montant' => 'required|numeric|min:1000', // On fixe un minimum de 1000 F pour les retraits
            'methode' => 'required|string|in:Wave,Orange Money,Free Money',
            'numero_compte' => 'required|string|min:9',
        ]);

        // 2. Vérification du solde disponible
        // On utilise la méthode soldeRetirable() qu'on a définie dans le modèle User
        $soldeDisponible = $user->soldeRetirable();

        if ($request->montant > $soldeDisponible) {
            return response()->json([
                'message' => 'Solde insuffisant pour effectuer ce retrait.',
                'solde_actuel' => $soldeDisponible
            ], 422);
        }

        // 3. Création de la demande de retrait
        $retrait = Retrait::create([
            'user_id' => $user->id,
            'montant' => $request->montant,
            'methode' => $request->methode,
            'numero_compte' => $request->numero_compte,
            'statut' => 'en_attente',
        ]);

        return response()->json([
            'message' => 'Votre demande de retrait a été enregistrée et est en cours de traitement.',
            'retrait' => $retrait
        ], 201);
    }
}