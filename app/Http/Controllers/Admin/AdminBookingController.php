<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index() {
    // On charge l'utilisateur qui a réservé ET le parking (avec son propriétaire)
    return Reservation::with(['user', 'parking.user'])
        ->latest()
        ->get();
}

public function validerRemboursement($id) {
    $paiement = Paiement::findOrFail($id);
    
    // On passe le paiement en 'rembourse'
    $paiement->update([
        'statut' => 'rembourse',
        'date_remboursement' => now()
    ]);

    return response()->json(['message' => 'Le remboursement a été marqué comme effectué.']);
}

public function listeRemboursements()
{
    // On récupère les paiements avec la réservation et l'utilisateur associé
    $remboursements = Paiement::with(['reservation.user', 'reservation.parking'])
        ->where('statut', 'remboursement_en_attente')
        ->get();

    // On transforme les données pour qu'elles correspondent exactement à ton interface TypeScript
    $data = $remboursements->map(function ($paiement) {
        return [
            'id' => $paiement->id,
            'reservation_id' => $paiement->reservation_id,
            'montant' => $paiement->montant,
            // On récupère le téléphone de la réservation (ou de l'user si tu préfères)
            'telephone' => $paiement->reservation->telephone, 
            'client_nom' => $paiement->reservation->user->name,
            'parking_nom' => $paiement->reservation->parking->nom,
            'date_annulation' => $paiement->updated_at->format('d/m/Y H:i'),
            'statut' => $paiement->statut,
        ];
    });

    return response()->json($data);
}



}
