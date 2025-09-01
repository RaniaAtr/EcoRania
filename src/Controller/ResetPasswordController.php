<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * Demande de réinitialisation de mot de passe
     */
    #[Route('', name: 'api_forgot_password_request', methods: ['POST'])]
    public function request(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['message' => 'Email manquant'], 400);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Ne pas révéler si l'utilisateur existe ou pas
        if (!$user) {
            return $this->json(['message' => 'Si un compte existe, un email a été envoyé.'], 200);
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);

            $emailMessage = (new TemplatedEmail())
                ->from(new Address('mailer@your-domain.com', 'EcoSupport'))
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context(['resetToken' => $resetToken]);

            $mailer->send($emailMessage);

        } catch (ResetPasswordExceptionInterface $e) {
            // Le token n'a pas pu être généré
            return $this->json(['message' => 'Impossible de générer le token.'], 500);
        } catch (\Throwable $e) {
            // Tout autre problème (Twig, mailer, etc.)
            return $this->json(['message' => 'Impossible d’envoyer l’email. Vérifiez la configuration.'], 500);
        }

        return $this->json(['message' => 'Si un compte existe, un email a été envoyé.'], 200);
    }

    /**
     * Réinitialisation du mot de passe avec token
     */
    #[Route('/reset/{token}', name: 'api_reset_password', methods: ['POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, string $token): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $plainPassword = $data['plainPassword'] ?? null;

        if (!$plainPassword) {
            return $this->json(['message' => 'Mot de passe manquant'], 400);
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json(['message' => 'Token invalide ou expiré'], 400);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->flush();
        $this->resetPasswordHelper->removeResetRequest($token);

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès'], 200);
    }
}
