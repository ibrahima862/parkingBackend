<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index()
    {
        // On récupère les réservations confirmées (qui ont généré un paiement)
        $transactions = Reservation::with(['user', 'parking'])
            ->where('statut', 'confirme')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                // Calcul de la commission (ex: 10%)
                $commission = $t->montant_total * 0.10; 
                
                return [
                    'id' => $t->id,
                    'reference' => 'TX-' . strtoupper(substr(md5($t->id), 0, 8)),
                    'client' => $t->user->name ?? 'Anonyme',
                    'parking' => $t->parking->nom ?? 'Espace supprimé',
                    'montant_brut' => (float)$t->montant_total,
                    'commission' => $commission,
                    'net_partenaire' => $t->montant_total - $commission,
                    'date' => $t->created_at->format('d/m/Y H:i'),
                    'methode' => 'PayTech (Mobile Money)' // Ou selon ta colonne
                ];
            });

        return response()->json($transactions);
    }
}