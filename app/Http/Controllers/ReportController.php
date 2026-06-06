<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Notifications\SignalementAuteurNotif;
use App\Notifications\SignalementProprioNotif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ReportController extends Controller
{

public function index(Request $request) {
    $reports = Report::with(['user', 'parking'])
        ->where('status', $request->status)
        ->latest()
        ->get();    
    return response()->json($reports);
}
   public function store(Request $request) {
    $validated = $request->validate([
        'parking_id' => 'required|integer|exists:parkings,id',
        'category' => 'required|string',
        'description' => 'required|string',
    ]);

    $userId = Auth::id();

    // 1. Vérifier si l'utilisateur a déjà réservé dans ce parking
    $hasReserved = \DB::table('reservations')
        ->where('user_id', $userId)
        ->where('parking_id', $validated['parking_id'])
        ->whereIn('statut', ['confirme', 'termine']) // On vérifie les réservations valides
        ->exists();

    if (!$hasReserved) {
        return response()->json([
            'message' => 'Vous devez avoir effectué au moins une réservation dans ce parking pour pouvoir le signaler.'
        ], 403); // 403 Forbidden
    }

    // 2. Création du signalement
    $report = Report::create([
        'user_id' => $userId,
        'parking_id' => $validated['parking_id'],
        'category' => $validated['category'],
        'description' => $validated['description'],
        'status' => 'en_attente' // Attention à l'orthographe 'status'
    ]);

    // 3. Alerte automatique si accumulation de signalements
    $count = Report::where('parking_id', $validated['parking_id'])
                   ->where('status', 'en_attente')
                   ->count();

    if ($count >= 5) {
        // Optionnel : Alerter l'admin ou suspendre temporairement le parking
        // \Log::warning("Le parking ID {$validated['parking_id']} a reçu plus de 5 signalements.");
    }

    return response()->json(['message' => 'Signalement enregistré avec succès'], 201);
}
public function adminAction(Request $request, $id)
{
    \Log::info("Admin action on report ID: $id with data: " . json_encode($request->all()));
    
    // Chargement complet des relations pour éviter le "N+1 problem"
    $report = Report::with(['parking.user', 'user'])->findOrFail($id);
    
    $validated = $request->validate([
        'action' => 'required|string|in:traite,rejete,delete_parking'
    ]);

    $action = $validated['action'];
    $message = "Action effectuée";

    // 1. Mise à jour de la base de données
    switch ($action) {
        case 'traite':
            $report->update(['status' => 'resolu']);
            $message = "Le signalement a été marqué comme résolu.";
            break;

        case 'rejete':
            $report->update(['status' => 'rejete']);
            $message = "Le signalement a été rejeté.";
            break;

        case 'delete_parking':
            $parking = $report->parking;
            if ($parking) {
                $parking->update(['is_active' => false]); 
                $report->update(['status' => 'resolu']);
                $message = "Le parking a été désactivé et le signalement clôturé.";
            } else {
                return response()->json(['message' => 'Parking introuvable'], 404);
            }
            break;
    }

    // 2. ENVOI DES NOTIFICATIONS
    try {
        // --- A. Notification à l'utilisateur (celui qui a signalé) ---
        if ($report->user) {
            $report->user->notify(new SignalementAuteurNotif($report, $action));
        }

        // --- B. Notification au propriétaire (le partenaire) ---
        // On ne notifie le propriétaire que si l'action est "traite" ou "delete_parking"
        if (in_array($action, ['traite', 'delete_parking'])) {
            $proprietaire = $report->parking?->user;
            
            if ($proprietaire) {
                // On adapte le type d'alerte pour le propriétaire
                $actionProprio = ($action === 'delete_parking') ? 'parking_suspended' : 'warning';
                $proprietaire->notify(new SignalementProprioNotif($report, $actionProprio));
            }
        }

    } catch (\Exception $e) {
        // Log l'erreur mais permet à l'admin de voir le succès de l'action DB
        \Log::error("Erreur d'envoi de notification (AdminAction) : " . $e->getMessage());
    }

    return response()->json([
        'status' => 'success',
        'message' => $message
    ]);
}
}
