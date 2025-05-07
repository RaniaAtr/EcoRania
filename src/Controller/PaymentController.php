<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Exception\ApiErrorException;

class PaymentController extends AbstractController
{
    #[Route('/payment/{id}', name: 'app_home')]
public function index(int $id, ActivityRepository $activityRepository, StripeService $stripeService): Response
{
    $activity = $activityRepository->find($id);

    if (!$activity) {
        throw $this->createNotFoundException('Activité non trouvée');
    }

    return $this->render('payment/index.html.twig', [
        'activity' => $activity,
        'stripe_public_key' => $stripeService->getPublicKey(),
    ]);
}

    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        StripeService $stripeService,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupération des données du formulaire
        $id_Activity = $request->request->get('id_Activity');
        $amount = (float) $request->request->get('amount');

        // Création d'une nouvelle commande
        $order = new Order();
        $order->setid_Activity($id_Activity);
        $order->setAmount($amount);
        $entityManager->persist($order);
        $entityManager->flush();

        try {
            // Création d'une session de paiement Stripe
            $successUrl = $this->generateUrl('app_payment_success', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            
            $cancelUrl = $this->generateUrl('app_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $session = $stripeService->createCheckoutSession(
                $amount,
                $id_Activity,
                $successUrl,
                $cancelUrl
            );

            // Enregistrement de l'ID de session dans la commande
            $order->setStripeSessionId($session->id);
            $entityManager->flush();

            // Redirection vers Stripe Checkout
            return $this->redirect($session->url);

        } catch (ApiErrorException $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la communication avec Stripe. Veuillez réessayer.');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/payment/success/{orderId}', name: 'app_payment_success')]
    public function success(int $orderId, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($orderId);
        
        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        return $this->render('payment/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/payment/cancel', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }

    #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function stripeWebhook(
        Request $request,
        StripeService $stripeService,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $this->getParameter('stripe.webhook_secret');

        try {
            $event = $stripeService->handleWebhook($payload, $sigHeader, $endpointSecret);
            
            // Traitement de l'événement de paiement réussi
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $order = $orderRepository->findByStripeSessionId($session->id);
                
                if ($order) {
                    $order->setIsPaid(true);
                    $order->setPaidAt(new \DateTimeImmutable());
                    $order->setStripePaymentId($session->payment_intent);
                    $entityManager->flush();
                }
            }
            
            return new Response('Webhooks processed successfully', Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return new Response('Webhook Error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}