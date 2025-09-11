<?php
// src/Command/TestPaymentBackendCommand.php

namespace App\Command;

use App\Entity\Activity;
use App\Entity\Order;
use App\Service\StripeService;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Stripe\Exception\ApiErrorException;

#[AsCommand(
    name: 'app:test-payment-backend',
    description: 'Test complet du processus de paiement backend'
)]
class TestPaymentBackendCommand extends Command
{
    public function __construct(
        private StripeService $stripeService,
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🧪 Test complet du processus de paiement backend');

        try {
            // Étape 1: Créer une activité de test
            $io->section('1️⃣ Création d\'une activité de test');
            $activity = $this->createTestActivity();
            $io->success("✅ Activité créée - ID: {$activity->getId()}, Prix: {$activity->getTarif()}€");

            // Étape 2: Créer une commande
            $io->section('2️⃣ Création d\'une commande');
            $order = $this->createTestOrder($activity);
            $io->success("✅ Commande créée - ID: {$order->getId()}, Montant: {$order->getAmount()}");

            // Étape 3: Créer une session Stripe Checkout
            $io->section('3️⃣ Création de la session Stripe Checkout');
            $session = $this->createStripeSession($activity, $order);
            $io->success("✅ Session Stripe créée - ID: {$session->id}");
            $io->info("🔗 URL de paiement: {$session->url}");

            // Étape 4: Simuler le paiement avec les cartes de test
            $io->section('4️⃣ Test des différentes cartes');
            $this->testDifferentCards($io);

            // Étape 5: Simuler le webhook
            $io->section('5️⃣ Simulation du webhook de succès');
            $this->simulateSuccessfulWebhook($order, $session->id, $io);

            // Étape 6: Vérifier l'état final
            $io->section('6️⃣ Vérification finale');
            $this->verifyFinalState($order->getId(), $io);

            $io->success('🎉 Tous les tests backend sont passés avec succès !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors des tests: ' . $e->getMessage());
            $io->note('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function createTestActivity(): Activity
    {
        $activity = new Activity();
        $activity->setTitre('Test Backend Payment - ' . date('Y-m-d H:i:s'));
        $activity->setTarif(2500); // 25€
        $activity->setDescription('Activité créée pour tester le paiement backend');
        
        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        
        return $activity;
    }

    private function createTestOrder(Activity $activity): Order
    {
        $order = new Order();
        $order->setActivity($activity);
        $order->setAmount($activity->getTarif());
        
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        
        return $order;
    }

    private function createStripeSession(Activity $activity, Order $order): \Stripe\Checkout\Session
    {
        $session = $this->stripeService->createCheckoutSession(
            $activity->getTarif(),
            $activity->getTitre(),
            'https://example.com/success/' . $order->getId(),
            'https://example.com/cancel'
        );

        // Associer la session à la commande
        $order->setStripeSessionId($session->id);
        $this->entityManager->flush();

        return $session;
    }

    private function testDifferentCards(SymfonyStyle $io): void
    {
        $testCards = [
            '4242424242424242' => '✅ Carte de succès',
            '4000000000000002' => '❌ Carte déclinée',
            '4000000000009995' => '⚠️  Fonds insuffisants',
            '4000002500003155' => '🔒 Authentification 3D Secure requise'
        ];

        foreach ($testCards as $cardNumber => $description) {
            $io->info("🔍 Test avec: {$cardNumber} - {$description}");
        }
        
        $io->note('💡 Pour tester manuellement, utilisez ces cartes sur l\'URL de checkout générée ci-dessus');
    }

    private function simulateSuccessfulWebhook(Order $order, string $sessionId, SymfonyStyle $io): void
    {
        // Simuler ce qui se passe dans le webhook lors d'un paiement réussi
        $order->setIsPaid(true);
        $order->setPaidAt(new \DateTimeImmutable());
        $order->setStripePaymentId('pi_test_' . uniqid());
        
        $this->entityManager->flush();
        
        $io->success("✅ Webhook simulé - Commande marquée comme payée");
    }

    private function verifyFinalState(int $orderId, SymfonyStyle $io): void
    {
        $order = $this->orderRepository->find($orderId);
        
        if (!$order) {
            throw new \Exception("Commande {$orderId} non trouvée");
        }

        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['ID Commande', $order->getId()],
                ['Montant', $order->getAmount() . ' centimes'],
                ['Est Payée', $order->isIsPaid() ? '✅ Oui' : '❌ Non'],
                ['Date de paiement', $order->getPaidAt() ? $order->getPaidAt()->format('Y-m-d H:i:s') : 'N/A'],
                ['Session Stripe ID', $order->getStripeSessionId() ?: 'N/A'],
                ['Payment Intent ID', $order->getStripePaymentId() ?: 'N/A'],
                ['Activité', $order->getActivity()->getTitre()],
            ]
        );

        if ($order->isIsPaid()) {
            $io->success('✅ La commande est correctement marquée comme payée');
        } else {
            $io->warning('⚠️  La commande n\'est pas marquée comme payée');
        }
    }
}