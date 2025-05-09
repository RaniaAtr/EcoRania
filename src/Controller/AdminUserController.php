<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/admin/users')]
class AdminUserController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // ✅ Permet à un admin de modifier les rôles d'un utilisateur
    #[Route('/{id}/roles', name: 'admin_update_roles', methods: ['PUT'])]
    public function updateRoles(int $id, Request $request, UserRepository $userRepository): JsonResponse
    {
        // Vérifier si l'utilisateur est un administrateur
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer les rôles envoyés dans la requête
        $data = json_decode($request->getContent(), true);
        $roles = $data['roles'] ?? [];

        // Vérifier que le format des rôles est valide (doit être un tableau)
        if (!is_array($roles)) {
            return $this->json(['message' => 'Le format des rôles est invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour les rôles de l'utilisateur
        $user->setRoles($roles);
        $this->em->flush();

        return $this->json(['message' => 'Rôles mis à jour avec succès']);
    }
}
