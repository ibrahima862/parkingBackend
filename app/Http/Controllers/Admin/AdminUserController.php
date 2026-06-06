<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        // On récupère uniquement les utilisateurs ayant le rôle 'client' (conducteur)
        $users = User::where('role', 'client')
            ->withCount('reservations')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'nb_reservations' => $user->reservations_count,
                    'date_inscription' => $user->created_at->format('d/m/Y'),
                    'statut' => $user->is_approved ? 'actif' : 'suspendu', // Exemple de logique
                ];
            });

        return response()->json($users);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // Inverse le statut actuel
        $user->is_approved = !$user->is_approved;
        $user->save();

        return response()->json([
            'message' => $user->is_approved ? 'Utilisateur débloqué' : 'Utilisateur suspendu',
            'statut' => $user->is_approved ? 'actif' : 'suspendu'
        ]);
    }
}