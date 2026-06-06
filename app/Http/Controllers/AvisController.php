<?php

namespace App\Http\Controllers;

use App\Models\AvisClient;
use App\Models\Reservation;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parking_id' => 'required|exists:parkings,id',
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:500',
        ]);

        
        $hasReserved = Reservation::where('user_id', auth()->id())
            ->where('parking_id', $validated['parking_id'])
            ->where('statut', ['confirme', 'termine'])
            ->exists();
            
        if (!$hasReserved) {
            return response()->json(['message' => 'Vous devez avoir terminé une réservation pour noter ce parking.'], 403);
        }
        
        $avis = AvisClient::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'parking_id' => $validated['parking_id']
            ],
            [
                'note' => $validated['note'],
                'commentaire' => $validated['commentaire']
            ]
        );

        return response()->json([
            'message' => 'Merci pour votre avis !',
            'avis' => $avis->load('user')
        ], 201);
    }
}