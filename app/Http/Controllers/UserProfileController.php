<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Reservation; // 1. Ne pas oublier l'import
use App\Models\VehiculeType;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function index()
    {
        $userId = auth()->user()->id;

        // Récupération des abonnements avec les infos du parking
        $abonnements = Abonnement::with(['parking'])
            ->where('user_id', $userId)
            ->get();

        // Récupération des réservations de l'utilisateur qui sont confirmées
        $reservations = Reservation::with('parking')
            ->where('user_id', $userId)
            ->where('statut', 'confirme') // On utilise where pour cumuler les conditions
            ->get();
        $vehicules = auth()->user()->vehicules()->with('type')->get();
        $tousLesTypes = VehiculeType::select('id', 'libelle', 'icon')->get(); // Retourner les deux collections
        
        // Retourner les deux collections
        return response()->json([
            'abonnements' => $abonnements,
            'reservations' => $reservations,
            'vehicules' => $vehicules,
            'categories' => $tousLesTypes,
            'user' => auth()->user()
        ]);
    }
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['phone'], // Assurez-vous que le champ dans la base de données s'appelle bien 'telephone'
        ]);

        return response()->json(['message' => 'Profil mis à jour avec succès', 'user' => $user]);
    }
}