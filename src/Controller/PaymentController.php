<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ActivityRepository;
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
    #[Route('/', name: 'app_home')]
    public function index(StripeService $stripeService): Response
    {
        return $this->render('payment/index.html.twig', [
            'stripe_public_key' => $stripeService->getPublicKey(),
        ]);
    }

    #[Route('/api/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        StripeService $stripeService,
        EntityManagerInterface $entityManager,
        ActivityRepository $activityRepository
    ): Response {
        // Récupère l'ID de l'activité depuis le formulaire
        $activityId = $request->request->get('activity_id');

        // Trouve l'activité en base
        $activity = $activityRepository->find($activityId);

        if (!$activity) {
            throw $this->createNotFoundException('Activité introuvable.');
        }

        // Crée une nouvelle commande liée à l'activité
        $order = new Order();
        $order->setActivity($activity);
        $order->setAmount($activity->getTarif());
        $order->setUser($this->getUser());
        $entityManager->persist($order);
        $entityManager->flush();

        try {
            // Crée une session Stripe Checkout
            $successUrl = $this->generateUrl('app_payment_success', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $cancelUrl = $this->generateUrl('app_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $session = $stripeService->createCheckoutSession(
                $activity->getTarif(),
                $activity->getTitre(),
                $successUrl,
                $cancelUrl
            );

            // Associe la session Stripe à la commande
            $order->setStripeSessionId($session->id);
            $entityManager->flush();

            // Redirige vers Stripe Checkout
            return $this->json(['url' => $session->url]);

        } catch (ApiErrorException $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la communication avec Stripe.');
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

            return new Response('Webhook traité avec succès', Response::HTTP_OK);

        } catch (\Exception $e) {
            return new Response('Erreur Webhook : ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // recuperer ses propres reservations 
    #[Route('/api/reservations', name: 'app_reservations', methods: ['GET'])]
    public function getReservations(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non autorisé'], 401);
        }

        $orders = $orderRepository->findBy(['user' => $user]);

        $data = array_map(fn($order) => [
            'id' => $order->getId(),
            'activity' => $order->getActivity()->getTitre(),
            'amount' => $order->getAmount(),
            'isPaid' => $order->isPaid(),
            'createdAt' => $order->getCreatedAt() ? $order->getCreatedAt()->format('Y-m-d H:i:s') : null,
        ], $orders);

        return $this->json($data);
    }
}
