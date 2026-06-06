<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Commission;
use App\Models\Paiement;
use App\Models\Parking;
use App\Services\PayTechService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbonnementController extends Controller
{
    protected $paytechService;

    /**
     * Injection du service PayTech via le constructeur
     */
    public function __construct(PayTechService $payTechService)
    {
        $this->paytechService = $payTechService;
    }


    public function index()
    {
        // Ajout de get() pour exécuter la requête
        $abonnements = Abonnement::with(['parking','vehicule'])
            ->where('user_id', auth()->user()->id)
            ->get();

        return response()->json($abonnements);
    }
    /**
     * ÉTAPE 1 : Initialiser l'abonnement et rediriger vers PayTech
     */
    public function store(Request $request)
    {
        // 1. Validation stricte des données
        $request->validate([
            'parking_id' => 'required|exists:parkings,id',
            'type' => 'required|string',
            'prix' => 'required|numeric|min:100',
            'matricule_vehicule' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            // 2. Création de l'enregistrement en base de données (Statut : en_attente)
            $abonnement = Abonnement::create([
                'user_id' => $user->id,
                'parking_id' => $request->parking_id,
                'type' => $request->type,
                'prix' => $request->prix,
                'date_debut' => Carbon::now(),
                'date_fin' => Carbon::now()->addMonth(),
                'statut' => 'en_attente',
                'matricule_vehicule' => strtoupper($request->matricule_vehicule),
            ]);
            
            Paiement::create([
              'montant' =>$abonnement->prix,
              'methode_paiement'=>'en ligne',
              'statut' =>'en_attente',
              'abonnement_id'=>$abonnement->id,
              'date_paiement'=>now()
            ]);
            // 3. Préparation pour le service PayTech
            // On injecte le montant_total dans l'objet pour la compatibilité avec ton Service
            $abonnement->prix = $request->prix;

            // 4. Appel du service PayTech
            $result = $this->paytechService->createPaymentMensuel($abonnement);

            // 5. Analyse de la réponse du service
            if (isset($result['success']) && $result['success'] == 1) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $result['redirect_url'],
                    'token' => $result['token'],
                    'abonnement_id' => $abonnement->id
                ]);
            }

            // Si PayTech renvoie une erreur (ex: Clés API invalides)
            Log::error("PayTech Init Error for Abo #{$abonnement->id}: ", $result);
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'initialisation du paiement.",
                'details' => $result['errors'] ?? 'Service indisponible'
            ], 400);

        } catch (\Exception $e) {
            Log::error("Abonnement Store Exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Une erreur interne est survenue.",
                'error' => $e->getMessage()
            ], 500);
        }
    }

     public function confirmation(Request $request, $id)
    {
        $abonnements = Abonnement::with('parking')->findOrFail($id);

        $isManualTest = !$request->has('token');
        $status = $isManualTest ? ['success' => 1] : $this->paytechService->checkPaymentStatus($request->token);

        if (isset($status['success']) && $status['success'] == 1) {

            return DB::transaction(function () use ($abonnements, $id) {
                // 1. Update de la réservation
                $abonnements->update(['statut' => 'actif']);

                // 2. Update du paiement
                Paiement::where('abonnement_id', $id)->update([
                    'statut' => 'effectue',
                    'date_paiement' => now()
                ]);

                // 3. GÉNÉRATION DE LA COMMISSION (Le nouveau bloc)
                $tauxCommission = 20;
                $montantCommission = $abonnements->prix * ($tauxCommission / 100);

                Commission::create([
                    'abonnement_id' => $abonnements->id,
                    'taux' => $tauxCommission,
                    'montant' => $montantCommission,
                    'statut' => 'valide',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Réservation et commission enregistrées',
                    'data' => $abonnements
                ], 200);
            });
        }

        return response()->json([
            'success' => false,
            'message' => 'Le paiement a échoué'
        ], 400);
    }
    /**
     * ÉTAPE 2 : Webhook (IPN) - Appelé par PayTech pour confirmer le succès
     */
    public function handleWebhook(Request $request)
    {
        // PayTech envoie les données en POST
        Log::info('PayTech Webhook Payment Received:', $request->all());

        $ref_command = $request->ref_command; // Format: RES-ID-TIME (défini dans ton service)
        $type_event = $request->type_event;  // 'sale_complete'

        if ($type_event === 'sale_complete' || $type_event === 'test_notification') {

            // Extraction de l'ID depuis la référence (RES-ID-TIMESTAMP)
            $parts = explode('-', $ref_command);
            $abonnementId = $parts[1] ?? null;

            if ($abonnementId) {
                $abonnement = Abonnement::find($abonnementId);

                if ($abonnement && $abonnement->statut !== 'actif') {
                    // Validation de l'abonnement
                    $abonnement->update(['statut' => 'actif']);

                    // Optionnel : On peut aussi logger le succès spécifique
                    Log::info("Abonnement #{$abonnementId} activé avec succès via Webhook.");
                }
            }
        }

        // Toujours répondre avec un code 200 à PayTech
        return response()->json(['status' => 'webhook_received']);
    }
}