<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->hasher = $hasher;
    }

    // ✅ Lister tous les utilisateurs (admin uniquement)
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $repository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $users = $repository->findAll();

        $data = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], $users);

        return $this->json($data);
    }

    // ✅ Créer un nouvel utilisateur
    #[Route('/create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
        {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['nom'], $data['prenom'])) {
            return $this->json(['message' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();

        $nom = isset($data['nom']) && is_string($data['nom']) ? $data['nom'] : null;
        $prenom = isset($data['prenom']) && is_string($data['prenom']) ? $data['prenom'] : null;
        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : null;
        $plainPassword = isset($data['password']) && is_string($data['password']) ? $data['password'] : null;

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);

        // ✅ Hash du mot de passe
        if ($plainPassword !== null) {
            $hashedPassword = $this->hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $roles = $data['roles'] ?? ['ROLE_USER'];
        $user->setRoles($roles);

        // ✅ Validation de l'entité User
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json($messages, Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur créé'], Response::HTTP_CREATED);
    }

    // ✅ Modifier ses propres données
    #[Route('/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(int $id, Request $request, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        if (!$currentUser || $currentUser->getId() !== $user->getId()) {
            return $this->json(['message' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (isset($data['prenom'])) $user->setPrenom($data['prenom']);

        if (isset($data['password'])) {
            $hashedPassword = $this->hasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $this->em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    // ✅ Supprimer un utilisateur
    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $repository): JsonResponse
    {
        $currentUser = $this->getUser();
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
            return $this->json(['message' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }

    // ✅ Voir ses propres infos
    #[Route('/me', name: 'user_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    // ✅ Admin : modifier les rôles d’un utilisateur
    #[Route('/{id}/roles', name: 'user_update_roles', methods: ['PUT'])]
    public function updateRoles(int $id, Request $request, UserRepository $userRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $roles = $data['roles'] ?? [];

        if (!is_array($roles)) {
            return $this->json(['message' => 'Format de rôle invalide'], Response::HTTP_BAD_REQUEST);
        }

        $user->setRoles($roles);
        $this->em->flush();

        return $this->json(['message' => 'Rôles mis à jour']);
    }

   // ✅ Déconnexion (log out) 
    #[Route('/logout', name: 'user_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        
        // Message de confirmation pour la déconnexion
        return $this->json(['message' => 'Déconnexion réussie.']);
    }
}