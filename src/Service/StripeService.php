<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private string $secretKey;
    private string $publicKey;

    public function __construct(string $secretKey, string $publicKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        Stripe::setApiKey($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Crée une session de paiement Stripe Checkout
     * 
     * @param float $amount Montant en euros
     * @param string $id_Activity id de l'activité
     * @param string $successUrl URL de redirection en cas de succès
     * @param string $cancelUrl URL de redirection en cas d'annulation
     * @return Session
     * @throws ApiErrorException
     */
    public function createCheckoutSession(float $amount, string $id_Activity, string $successUrl, string $cancelUrl): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $id_Activity,
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    /**
     * Vérifie un webhook Stripe pour confirmer un paiement
     * 
     * @param string $payload Le contenu brut du webhook
     * @param string $sigHeader L'en-tête de signature
     * @param string $endpointSecret Le secret du webhook
     * @return \Stripe\Event
     * @throws \UnexpectedValueException
     */
    public function handleWebhook(string $payload, string $sigHeader, string $endpointSecret)
    {
        return \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, $endpointSecret
        );
    }
}