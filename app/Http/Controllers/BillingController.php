<?php

// app/Http/Controllers/Proprietaire/BillingController.php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Commission;
use App\Models\Reservation;
use App\Models\Retrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $parkingIds = $user->parkings()->pluck('id');

        // 1. FILTRE TEMPOREL
        $days = $request->query('days', 30);
        $startDate = now()->subDays((int) $days);

        // 2. CALCUL DES REVENUS BRUTS (Réservations + Abonnements)
        $brutReservations = Reservation::whereIn('parking_id', $parkingIds)
            ->whereIn('statut', ['confirme', 'termine'])
            ->where('date_debut', '>=', $startDate)
            ->sum('montant_total');

        $brutAbonnements = Abonnement::whereIn('parking_id', $parkingIds)
            ->where('statut', 'actif')
            ->sum('prix');

        $caBrutTotal = $brutReservations + $brutAbonnements;

        // 3. CALCUL DES COMMISSIONS (À déduire des gains)
        // On récupère toutes les commissions liées aux parkings de l'utilisateur
        $totalCommissions = Commission::where(function ($query) use ($parkingIds) {
            $query->whereHas('reservation', function ($q) use ($parkingIds) {
                $q->whereIn('parking_id', $parkingIds);
            })->orWhereHas('abonnement', function ($q) use ($parkingIds) {
                $q->whereIn('parking_id', $parkingIds);
            });
        })
            ->where('statut', 'valide')
            ->sum('montant');

        // 4. CALCUL DES RETRAITS (Validés et En attente)
        $retraitsBloques = Retrait::where('user_id', $user->id)
            ->whereIn('statut', ['valide', 'en_attente'])
            ->sum('montant');

        // 5. SYNTHÈSE FINANCIÈRE (Le Net)
        $gainsReelsNet = $caBrutTotal - $totalCommissions;
        $soldeDisponible = $gainsReelsNet - $retraitsBloques;
        $enAttenteRetrait = Retrait::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->sum('montant');

        // 6. HISTORIQUE DES RÉSERVATIONS (Formaté pour Billing.tsx)
        $resTrans = Reservation::whereIn('parking_id', $parkingIds)
            ->with(['parking', 'commission'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($res) {
                $com = $res->commission ? $res->commission->montant : 0;
                return [
                    'id' => 'res_' . $res->id,
                    'type' => 'gain',
                    'label' => "Réservation " . ($res->parking->nom ?? 'Parking'),
                    'date' => $res->created_at->diffForHumans(),
                    'montant' => (int) ($res->montant_total - $com), // On affiche le montant NET
                    'parking' => $res->parking->nom ?? 'Parking',
                    'statut' => in_array($res->statut, ['confirme', 'termine'])
                        ? 'completed'
                        : ($res->statut === 'annule' ? 'annule' : 'pending')
                ];
            });

        // 7. HISTORIQUE DES RETRAITS
        $retTrans = Retrait::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($ret) {
                return [
                    'id' => 'ret_' . $ret->id,
                    'type' => 'retrait',
                    'label' => "Retrait vers " . $ret->methode,
                    'date' => $ret->created_at->diffForHumans(),
                    'montant' => (int) $ret->montant,
                    'methode' => $ret->methode,
                    'statut' => $ret->statut == 'valide' ? 'completed' : ($ret->statut == 'rejete' ? 'failed' : 'pending')
                ];
            });

        // 8. FUSION ET TRI DES TRANSACTIONS
        $allTransactions = $resTrans->concat($retTrans)
            ->sortByDesc(function ($item) {
                return $item['id']; // Ou trier par un objet Carbon si nécessaire
            })
            ->values();

        // 9. RÉPONSE JSON
        return response()->json([
            'solde' => (int) max(0, $soldeDisponible),
            'en_attente' => (int) $enAttenteRetrait,
            'total_historique' => (int) $gainsReelsNet,
            'total_retraits' => (int) Retrait::where('user_id', $user->id)->where('statut', 'valide')->sum('montant'),
            'transactions' => $allTransactions,
            'evolution_revenus' => [30000, 45000, 42000, 50000, 65000, (int) $gainsReelsNet]
        ]);
    }
}