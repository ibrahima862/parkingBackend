<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Retrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminRetraitController extends Controller
{
    /**
     * Liste toutes les demandes avec statistiques globales
     */
    public function index()
    {
        // On récupère les retraits avec les infos du partenaire (user)
        $retraits = Retrait::with('user:id,name,email')
            ->orderByRaw("FIELD(statut, 'en_attente', 'valide', 'rejete')")
            ->orderBy('created_at', 'desc')
            ->get();

        // Statistiques pour les compteurs du haut
        $stats = [
            'total_a_payer' => Retrait::where('statut', 'en_attente')->sum('montant'),
            'nb_attente' => Retrait::where('statut', 'en_attente')->count(),
            'total_paye_mois' => Retrait::where('statut', 'valide')
                ->whereMonth('date_paiement', now()->month)
                ->sum('montant'),
        ];

        return response()->json([
            'retraits' => $retraits,
            'stats' => $stats
        ]);
    }

    /**
     * Valider un retrait après avoir effectué le transfert (Wave/OM)
     */
    public function valider(Request $request, $id)
    {
        $request->validate([
            'reference' => 'required|string|unique:retraits,reference_transaction',
        ]);

        $retrait = Retrait::findOrFail($id);

        if ($retrait->statut !== 'en_attente') {
            return response()->json(['message' => 'Ce retrait a déjà été traité.'], 422);
        }

        $retrait->update([
            'statut' => 'valide',
            'reference_transaction' => $request->reference,
            'date_paiement' => now(),
        ]);

        return response()->json([
            'message' => 'Le retrait a été marqué comme payé.',
            'retrait' => $retrait
        ]);
    }

    /**
     * Rejeter une demande de retrait
     */
    public function rejeter(Request $request, $id)
    {
        $request->validate([
            'motif' => 'required|string|min:5',
        ]);

        $retrait = Retrait::findOrFail($id);

        if ($retrait->statut !== 'en_attente') {
            return response()->json(['message' => 'Ce retrait a déjà été traité.'], 422);
        }

        $retrait->update([
            'statut' => 'rejete',
            'motif_rejet' => $request->motif,
        ]);

        return response()->json([
            'message' => 'La demande de retrait a été rejetée.',
            'retrait' => $retrait
        ]);
    }
}