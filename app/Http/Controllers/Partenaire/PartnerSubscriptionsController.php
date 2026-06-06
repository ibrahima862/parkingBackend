<?php

namespace App\Http\Controllers\Partenaire;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerSubscriptionsController extends Controller
{
    /**
     * Récupère les abonnements liés aux parkings du partenaire connecté.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. On récupère les IDs des parkings appartenant à ce partenaire
        $parkingIds = $user->parkings()->pluck('id');

        // 2. On récupère les abonnements de ces parkings uniquement
        $abonnements = Abonnement::whereIn('parking_id', $parkingIds)
            ->with(['user:id,name,telephone', 'parking:id,nom']) 
            ->latest() // Les plus récents en premier
            ->get();

        // 3. On formate la réponse pour correspondre exactement à ton interface React
        $formatted = $abonnements->map(function ($sub) {
            return [
                'id' => $sub->id,
                'user' => [
                    'name' => $sub->user->name ?? 'Anonyme',
                    'telephone' => $sub->user->telephone ?? 'N/A',
                ],
                'matricule_vehicule' => $sub->matricule_vehicule,
                'plan' => [
                    'nom' => $sub->type ?? 'Mensuel Standard', // Utilise 'type' ou une relation plan
                ],
                'date_fin' => $sub->date_fin,
                'statut' => $sub->statut, // 'actif', 'en_attente', 'expire'
                'date_debut' => $sub->date_debut,
                'prix' => $sub->prix,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Action pour valider un abonnement en attente.
     */
    public function validateSubscription($id)
    {
        $user = Auth::user();
        $parkingIds = $user->parkings()->pluck('id');

        // On s'assure que l'abonnement appartient bien à un parking du partenaire
        $abonnement = Abonnement::whereIn('parking_id', $parkingIds)->findOrFail($id);

        $abonnement->update([
            'statut' => 'actif',
            'date_debut' => now(),
            'date_fin' => now()->addMonth(), // Ajuste selon la logique de ton offre
        ]);

        return response()->json([
            'message' => 'L\'abonnement a été activé avec succès.',
            'status' => 'success'
        ]);
    }
}