<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Reservation;
use App\Models\Retrait;
use App\Models\User;
use App\Models\Parking;
use Carbon\Carbon;
use Illuminate\Http\Request;


class AdminParkingController extends Controller
{
    /**
     * Liste des parkings en attente de validation
     */
    public function index()
    {
        $pendingParkings = Parking::with('user','services','plans')
            ->where('statut', 'en_attente')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pendingParkings);
    }

    /**
     * Liste des futurs propriétaires en attente d'approbation
     */
    public function getPendingProprios()
    {

        try {
            return User::where('role', 'proprietaireparking')
                ->where('is_approved', false)
                ->get();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Approuver un utilisateur pour qu'il devienne propriétaire actif
     */
    public function approveUser(User $user)
    {
        $user->update(['is_approved' => true]);

        return response()->json([
            'message' => "L'utilisateur {$user->name} est désormais un propriétaire approuvé."
        ]);
    }
    public function desapproveUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        // Delete related parkings first
        Parking::where('proprietaire_id', $id)->delete();

        // Delete the user
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'The user and their associated parkings have been deleted.'
        ], 200);
    }
    /**
     * Valider un parking pour le rendre public
     */
    public function approveParking(Request $request, Parking $parking)
    {
        // 1. Validation des données reçues du GeoModal
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // 2. Mise à jour avec les coordonnées validées/corrigées par l'admin
        $parking->update([
            'statut' => 'valide',
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude']
        ]);

        // 3. Log de l'activité (optionnel : inclure les coordonnées pour l'audit)
        Activity::log(
            'approve',
            "Parking {$parking->nom} validé (Lat: {$validated['latitude']}, Lng: {$validated['longitude']})",
            auth()->user()->name
        );

        return response()->json([
            'message' => "Le parking {$parking->nom} est maintenant visible par les clients avec sa position validée."
        ]);
    }
    public function rejectParking($id)
{
    // 1. Utiliser findOrFail pour renvoyer une 404 automatiquement si l'ID n'existe pas
    $parking = Parking::findOrFail($id);

    // 2. Vérifier si le parking n'est pas déjà traité (optionnel)
    if ($parking->statut === 'rejete') {
        return response()->json(['message' => 'Ce parking est déjà refusé.'], 400);
    }

    // 3. Mise à jour et suppression (SoftDelete)
    $parking->update(['statut' => 'rejete']);
    $parking->delete();

    // 4. Log de l'activité
    // Note : On utilise l'ID ou le nom avant que l'objet ne soit potentiellement altéré
    Activity::log(
        'reject',
        "Parking #{$parking->id} - {$parking->nom} refusé",
        auth()->user()->name ?? 'Système'
    );

    return response()->json([
        'status' => 'success',
        'message' => "Le parking \"{$parking->nom}\" a été refusé avec succès."
    ]);
}
    public function getGlobalStats()
    {
        // 1. Chiffre d'Affaires Total (Somme des réservations confirmées)
        $ca_total = Reservation::where('statut', 'confirme')->sum('montant_total');

        // 2. Retraits en attente (Somme des retraits statut 'en_attente')
        $retraits_somme = Retrait::where('statut', 'en_attente')->sum('montant');

        // 3. Taux d'approbation des parkings (Parkings 'actif' / Total soumis)
        $totalParkings = Parking::count();
        $parkingsActifs = Parking::where('statut', 'valide')->count();
        $taux_approbation = $totalParkings > 0 ? round(($parkingsActifs / $totalParkings) * 100) : 0;

        // 4. Temps moyen d'approbation (basé sur la différence entre création et validation du parking)
        // Note: Assure-toi d'avoir un champ 'valide_at' ou utilise 'updated_at' si c'est la date de validation
        $temps_moyen = Parking::where('statut', 'valide')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->first()->avg_hours ?? 0;

        // 5. Validations du mois (Parkings validés ce mois-ci)
        $validations_mois = Parking::where('statut', 'valide')
            ->whereMonth('updated_at', Carbon::now()->month)
            ->count();

        // 6. Croissance CA (Comparaison ce mois vs mois dernier)
        $ca_mois_dernier = Reservation::where('statut', 'confirme')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('montant_total');

        $ca_ce_mois = Reservation::where('statut', 'confirme')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('montant_total');

        $croissance_ca = $ca_mois_dernier > 0
            ? round((($ca_ce_mois - $ca_mois_dernier) / $ca_mois_dernier) * 100)
            : 0;
        $activities = Activity::where('user_name',auth()->user()->name)->latest()->take(5)->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'type' => $a->type,
                'msg' => $a->message,
                'user' => $a->user_name,
                'time' => $a->created_at->diffForHumans(),
            ];
        });
        return response()->json([
            'ca_total' => $ca_total,
            'retraits_somme' => $retraits_somme,
            'taux_approbation' => $taux_approbation,
            'temps_moyen' => round($temps_moyen, 1),
            'validations_mois' => $validations_mois,
            'croissance_ca' => $croissance_ca,
            'alertes_actives' => 0,
            'croissance_proprios' => User::where('role', 'proprietaire')->where('is_approved', 0)->count(),
            'recent_activities' => $activities,
        ]);
    }

    // la recuperation des statistiques pour les transactions et les paiements
    public function getPaymentStats()
    {
        $transactions = Reservation::with(['user', 'parking'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($t) => [
                'id' => "#TR-{$t->id}",
                'client' => $t->user->name ?? 'Client inconnu',
                'parking' => $t->parking->nom ?? 'Parking supprimé',
                'total' => $t->montant_total,
                'commission' => $t->montant_total * 0.1, // 10%
                'date' => $t->created_at->format('d M, H:i'),
                'status' => $t->statut === 'confirme' ? 'success' : 'pending'
            ]);

        return response()->json([
            'volume_total' => Reservation::where('statut', 'confirme')->sum('montant_total'),
            'commission_total' => Reservation::where('statut', 'confirme')->sum('montant_total') * 0.1,
            'en_attente_retrait' => Retrait::where('statut', 'en_attente')->sum('montant'),
            'transactions' => $transactions
        ]);
    }



    public function getParkings()
    {
        // On récupère les parkings avec le nom du proprio et les réservations actives
        $parkings = Parking::with([
            'user',
            'reservations' => function ($q) {
                $q->where('statut', 'confirme')
                    ->where('date_debut', '<=', Carbon::now())
                    ->where('date_fin', '>=', Carbon::now());
            }
        ])
            ->withSum([
                'reservations as ca_total' => function ($q) {
                    $q->where('statut', 'confirme')
                    ;
                }
            ], 'montant_total')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nom' => $p->nom,
                    'proprietaire' => $p->user->name ?? 'Inconnu',
                    'localisation' => $p->quartier . ', ' . $p->departement,
                    'capacite' => $p->capacite,
                    'occupe' => $p->places_occupees,
                    'statut' => $p->statut,
                    'ca_total' => $p->ca_total ?? 0,
                    'image' => $p->image
                ];
            });

        return response()->json($parkings);
    }

}