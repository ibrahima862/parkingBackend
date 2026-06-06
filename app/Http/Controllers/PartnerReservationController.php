<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PartnerReservationController extends Controller
{
    /**
     * Liste toutes les réservations liées aux parkings du partenaire connecté.
     */
    public function index(Request $request)
{
    try {
        $partnerId = $request->user()->id;

        $reservations = Reservation::whereHas('parking', function ($query) use ($partnerId) {
            $query->where('proprietaire_id', $partnerId);
        })
        ->where('statut', 'confirme')
        ->with(['client'])
        ->orderBy('date_debut', 'desc')
        ->get();

        $formattedReservations = $reservations->map(function ($res) {
            return [
                'id'             => $res->id,
                'client'         => $res->prenom_conducteur . ' ' . $res->nom_conducteur,
                'plaque'         => $res->matricule_vehicule,
                'parking_status' => $res->parking_status ?? 'attendu',
                'status_paiement'=> $res->statut, 
                'heure'          => Carbon::parse($res->date_debut)->format('H:i'),
                'tel'            => $res->telephone ?? '7x xxx xx xx',
            ];
        });

        return response()->json($formattedReservations, 200);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Erreur'], 500);
    }
}

    /**
     * Met à jour le statut physique du véhicule (Arrivée/Départ).
     */
    public function updateStatus(Request $request, $id)
    {
        // 1. Validation des données entrantes
        $request->validate([
            'parking_status' => 'required|in:garé,sorti,attendu'
        ]);

        try {
            $partnerId = $request->user()->id;

            // 2. Trouver la réservation ET vérifier qu'elle appartient bien à un parking du partenaire
            $reservation = Reservation::whereHas('parking', function ($query) use ($partnerId) {
                $query->where('proprietaire_id', $partnerId);                           
            })->findOrFail($id);

            // 3. Mise à jour
            $reservation->update([
                'parking_status' => $request->parking_status
            ]);

            // Si parking_status == 'sorti', on pourrait libérer la place officiellement 
            // ou envoyer une notification de remerciement au client.

            return response()->json([
                'message' => 'Statut mis à jour avec succès',
                'reservation' => $reservation
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Réservation introuvable ou accès non autorisé'], 404);
        } catch (\Exception $e) {
            Log::error("Erreur Update Status: " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la mise à jour'], 500);
        }
    }
}