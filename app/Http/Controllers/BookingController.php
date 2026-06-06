<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Paiement;
use App\Models\Parking;
use App\Models\Reservation;
use App\Services\PayTechService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    // On déclare la propriété sans l'initialiser ici
    protected $paytechService;

    // On utilise le constructeur pour injecter le service (C'est ici que l'erreur est fixée)
    public function __construct(PaytechService $paytechService)
    {
        $this->paytechService = $paytechService;
    }
    public function index()
    {
        // On récupère les réservations de l'utilisateur connecté
        $reservations = Reservation::with([
            'parking' => function ($query) {
                $query->select('id', 'nom', 'quartier', 'image'); // On ne prend que le nécessaire
            }
        ])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations);
    }

    public function store(Request $request)
    {
        // 1. VALIDATION STRICTE
        $validated = $request->validate([
            'parking_id' => 'required|exists:parkings,id',
            'date_debut' => 'required|date|after:' . now()->subMinutes(5)->toDateTimeString(),
            'date_fin' => 'required|date|after:date_debut',
            'telephone' => 'required|string',
            'nom_conducteur' => 'required|string|max:255',
            'prenom_conducteur' => 'required|string|max:255',
            'matricule_vehicule' => 'required|string|max:50',
        ]);

        try {
            return DB::transaction(function () use ($validated) {

                Reservation::where('parking_id', $validated['parking_id'])
                    ->whereIn('statut', ['en_attente', 'confirme'])
                    ->where('date_fin', '<', now())
                    ->update(['statut' => 'termine']);

                // 3. RÉCUPÉRATION DU PARKING (Lock for update pour éviter les réservations simultanées)
                $parking = Parking::with('plans')->lockForUpdate()->findOrFail($validated['parking_id']);

                // 4. VÉRIFICATION DE DISPONIBILITÉ RÉELLE
                $reservationsActives = Reservation::where('parking_id', $parking->id)
                    ->whereIn('statut', ['en_attente', 'confirme'])
                    ->where(function ($query) use ($validated) {
                        $query->where('date_debut', '<', $validated['date_fin'])
                            ->where('date_fin', '>', $validated['date_debut']);
                    })->count();

                if ($reservationsActives >= $parking->capacite) {
                    return response()->json([
                        'message' => "Désolé, ce parking est complet pour la période sélectionnée."
                    ], 422);
                }

                // 5. CALCUL DU MONTANT (Logique 1h commencée = 1h due)
                $debut = Carbon::parse($validated['date_debut']);
                $fin = Carbon::parse($validated['date_fin']);

                // Calculer la différence en heures (arrondi au supérieur)
                $dureeEnHeures = ceil($debut->floatDiffInHours($fin));
                if ($dureeEnHeures <= 0)
                    $dureeEnHeures = 1;

                // Priorité : Plan spécifique > Prix de base parking > Défaut 500
                $prixUnitaire = $parking->plans->first()->prix
                    ?? $parking->prix_base
                    ?? 500;

                $montantTotal = $dureeEnHeures * $prixUnitaire;

                // 6. CRÉATION DE LA RÉSERVATION
                $reservation = Reservation::create([
                    'user_id' => auth()->id(),
                    'parking_id' => $parking->id,
                    'nom_conducteur' => $validated['nom_conducteur'],
                    'prenom_conducteur' => $validated['prenom_conducteur'],
                    'matricule_vehicule' => $validated['matricule_vehicule'],
                    'telephone' => $validated['telephone'],
                    'date_debut' => $validated['date_debut'],
                    'date_fin' => $validated['date_fin'],
                    'montant_total' => $montantTotal,
                    'statut' => 'en_attente',
                ]);
                Paiement::create([
                    'reservation_id' => $reservation->id,
                    'montant' => $montantTotal,
                    'methode_paiement' => 'en ligne',
                    'statut' => 'en_attente',
                    'date_paiement' => now(),
                ]);
                // 7. GÉNÉRATION DU PAIEMENT PAYTECH
                try {
                    $paiement = $this->paytechService->createPaymentHoraire($reservation);

                    if (isset($paiement['success']) && $paiement['success'] == 1) {
                        // On ne met pas "paye" ici ! On attend la confirmation réelle.
                        return response()->json([
                            'redirect_url' => $paiement['redirect_url'],
                            'reservation_id' => $reservation->id
                        ], 201);
                    }

                    throw new \Exception("Réponse PayTech invalide");

                } catch (\Exception $e) {
                    // Si PayTech échoue, on annule la réservation pour ne pas bloquer une place inutilement
                    $reservation->delete();
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            Log::error("Erreur critique Store Reservation : " . $e->getMessage());
            return response()->json([
                'error' => "Une erreur est survenue lors de la réservation : " . $e->getMessage()
            ], 500);
        }
    }

    public function confirmation(Request $request, $id)
    {
        $reservation = Reservation::with('parking')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $isManualTest = !$request->has('token');
        $status = $isManualTest ? ['success' => 1] : $this->paytechService->checkPaymentStatus($request->token);
        if ($reservation->statut === 'confirme') {
            return response()->json([
                'success' => true,
                'message' => 'Réservation déjà confirmée',
                'data' => $reservation
            ]);
        }
        if (isset($status['success']) && $status['success'] == 1) {

            return DB::transaction(function () use ($reservation, $id) {
                // 1. Update de la réservation
                $reservation->update(['statut' => 'confirme']);

                // 2. Update du paiement
                Paiement::where('reservation_id', $id)->update([
                    'statut' => 'effectue',
                    'date_paiement' => now()
                ]);

                // 3. GÉNÉRATION DE LA COMMISSION (Le nouveau bloc)
                $tauxCommission = 10; // À mettre idéalement dans config('parking.commission')
                $montantCommission = $reservation->montant_total * ($tauxCommission / 100);

                Commission::create([
                    'reservation_id' => $reservation->id,
                    'taux' => $tauxCommission,
                    'montant' => $montantCommission,
                    'statut' => 'valide', // Directement valide car le paiement est confirmé
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Réservation et commission enregistrées',
                    'data' => $reservation
                ], 200);
            });
        }

        return response()->json([
            'success' => false,
            'message' => 'Le paiement a échoué'
        ], 400);
    }
    public function show($id)
    {
        // Logique pour afficher les détails d'une réservation spécifique
    }

    public function update(Request $request, $id)
    {
        // Logique pour mettre à jour une réservation existante
    }

    public function destroy($id)
    {
        $reservation = Reservation::where('id', $id)
        ->where('user_id', auth()->id())
        ->where('statut', 'confirme')
        ->firstOrFail();

    $debut = Carbon::parse($reservation->date_debut);
    
    // 1. Vérifier si la réservation n'a pas déjà commencé
    if (now()->greaterThan($debut)) {
        return response()->json(['message' => 'Impossible d\'annuler une réservation déjà commencée.'], 422);
    }

    return DB::transaction(function () use ($reservation) {
        // 2. Changer le statut
        $reservation->update(['statut' => 'annule']);

        // 3. Mettre à jour le paiement
        $paiement = Paiement::where('reservation_id', $reservation->id)->first();
        if ($paiement) {
            $paiement->update(['statut' => 'remboursement_en_attente']);
        }

        // 4. Annuler la commission associée
        Commission::where('reservation_id', $reservation->id)->update(['statut' => 'annule']);

        // 🔔 Optionnel : Envoyer un mail au proprio et à l'admin
        
        return response()->json(['message' => 'Réservation annulée. Votre remboursement est en cours de traitement.']);
    });
    }
}
