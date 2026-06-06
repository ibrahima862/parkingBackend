<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrlRequest = "https://paytech.sn/api/payment/request-payment";
    protected $baseUrlCheck = "https://paytech.sn/api/payment/check-payment/";

    public function __construct()
    {
        $this->apiKey = config('services.paytech.api_key');
        $this->apiSecret = config('services.paytech.api_secret');
    }

    /**
     * Crée la demande de paiement
     */
    private function createPayment($id, $amount, $name, $type = 'RES')
    {
        if ($amount < 100)
            $amount = 100;
        // 2. L'URL de ton TUNNEL Ngrok (pour que PayTech parle à ton Laravel en secret)
        $ngrokUrl = "https://indissolubly-unmediating-tressa.ngrok-free.dev";

        $data = [
            'item_name' => $name,
            'item_price' => $amount,
            'command_name' => "Commande #" . $id,
            'ref_command' => $type . "-" . $id . "-" . time(),
            'env' => 'test',
            'currency' => 'XOF',
            'ipn_url' => $ngrokUrl . "/paythech/ipn", // PayTech envoie la preuve ici
            'success_url' => $ngrokUrl . "/api/payment-bridge?id=" . $id,
            'cancel_url' => $ngrokUrl . "/api/payment-bridge?cancel=1",
            'custom_field' => json_encode(['id' => $id]),
        ];

        return $this->sendRequest($data);
    }
    private function sendRequest($data)
    {
        try {
            $response = Http::withHeaders([
                'API_KEY' => $this->apiKey,
                'API_SECRET' => $this->apiSecret,
                'Accept' => 'application/json',
            ])->post($this->baseUrlRequest, $data);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('PayTech Request Exception: ' . $e->getMessage());
            return ['success' => -1, 'errors' => [$e->getMessage()]];
        }
    }
    public function createPaymentHoraire($reservation)
    {
        return $this->createPayment(
            $reservation->id,
            (int) $reservation->montant_total,
            "Parking " . ($reservation->parking->nom ?? 'SenovaPark'),
            'RES'
        );
    }

    public function createPaymentMensuel($abonnement)
    {
        return $this->createPayment(
            $abonnement->id,
            (int) $abonnement->prix,
            "abonnement " . ($abonnement->parking->nom ?? 'SenovaPark'),
            'SUB'
        );
    }
    /**
     * VÉRIFICATION : Interroge PayTech pour savoir si le token est payé
     * Très utile pour le développement en local (localhost)
     */
    public function checkPaymentStatus($token)
    {
        try {
            $response = Http::withHeaders([
                'API_KEY' => $this->apiKey,
                'API_SECRET' => $this->apiSecret,
                'Accept' => 'application/json',
            ])->get($this->baseUrlCheck . $token);

            if ($response->failed()) {
                return ['success' => -1, 'errors' => ['Impossible de vérifier le paiement']];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('PayTech Check Exception: ' . $e->getMessage());
            return ['success' => -1, 'errors' => [$e->getMessage()]];
        }
    }
}