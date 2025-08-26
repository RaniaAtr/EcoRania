<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/api/activities')]
class ActivityController extends AbstractController
{
    /**
     * Liste toutes les activités disponibles ((accessible à tous, même sans authentification))
     */
    #[Route('', methods: ['GET'])]
    public function index(ActivityRepository $repo): JsonResponse
    {
        return $this->json($repo->findAll());
    }

    /**
     * Détail d'une activité (accessible par tous les utilisateurs)
     */
    #[Route('/{id}', methods: ['GET'])]
    public function show(Activity $activity): JsonResponse
    {
        return $this->json($activity);
    }

 /**
 * Créer une activité (accessible uniquement pour les administrateurs)
 */
    #[Route('', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker): JsonResponse
{
    // Vérification si l'utilisateur est un admin
    if (!$authChecker->isGranted('ROLE_ADMIN')) {
        return $this->json(['message' => 'Access denied'], 403);
    }

    $data = json_decode($request->getContent(), true);

    $activity = new Activity();
    $activity->setTitre($data['titre'] ?? '');
    $activity->setAdresse($data['adresse'] ?? '');
    $activity->setTag($data['tag'] ?? '');
    $activity->setImage($data['image'] ?? '');

    // Gestion du tarif (stocké en float)
    $tarif = isset($data['tarif']) ? (float) $data['tarif'] : 0;
    $activity->setTarif($tarif);

    // Gestion de la durée (stockée en minutes)
    $duree = isset($data['duree']) ? (int) $data['duree'] : 0; 
    $activity->setDuree($duree);

    // Gestion de la date
    if (isset($data['date'])) {
        try {
            $activity->setDate(new \DateTimeImmutable($data['date'], new \DateTimeZone('Europe/Paris')));
        } catch (\Exception $e) {
            return $this->json(['message' => 'Format de date invalide'], 400);
        }
    }

    $em->persist($activity);
    $em->flush();

    // Préparer les données pour le frontend
    $formattedActivity = [
        'id' => $activity->getId(),
        'titre' => $activity->getTitre(),
        'adresse' => $activity->getAdresse(),
        'date' => $activity->getDate() ? $activity->getDate()->format('Y-m-d H:i:s') : null,
        'tag' => $activity->getTag(),
        'tarif' => $activity->getTarif(),   // nombre pur, pas de "€"
        'image' => $activity->getImage(),
        'duree' => $activity->getDuree(),   // en minutes
    ];

    return $this->json($formattedActivity, 201);
}



    /**
 * Modifier une activité (accessible uniquement pour les administrateurs)
 */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        Request $request,
        Activity $activity,
        EntityManagerInterface $em,
        AuthorizationCheckerInterface $authChecker
    ): JsonResponse {
        // Vérification si l'utilisateur est un admin
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Champs simples
        $activity->setTitre($data['titre'] ?? $activity->getTitre());
        $activity->setAdresse($data['adresse'] ?? $activity->getAdresse());
        $activity->setTag($data['tag'] ?? $activity->getTag());
        $activity->setTarif(isset($data['tarif']) ? (float) $data['tarif'] : $activity->getTarif());
        $activity->setImage($data['image'] ?? $activity->getImage());

        // Gestion de la date (DateTimeImmutable obligatoire)
        if (!empty($data['date'])) {
            try {
                $activity->setDate(new \DateTimeImmutable($data['date'], new \DateTimeZone('Europe/Paris')));
            } catch (\Exception $e) {
                return $this->json(['message' => 'Format de date invalide'], 400);
            }
        }

        // Gestion de la durée (en minutes, nombre pur)
        if (isset($data['duree'])) {
            $activity->setDuree((int) $data['duree']);
        }

        $em->flush();

        // Retourner une réponse formatée
        $formattedActivity = [
            'id' => $activity->getId(),
            'titre' => $activity->getTitre(),
            'adresse' => $activity->getAdresse(),
            'date' => $activity->getDate() ? $activity->getDate()->format('Y-m-d H:i:s') : null,
            'tag' => $activity->getTag(),
            'tarif' => $activity->getTarif(),
            'image' => $activity->getImage(),
            'duree' => $activity->getDuree(),
        ];

        return $this->json($formattedActivity);
    }

    /**
     * Supprimer une activité (accessible uniquement pour les administrateurs)
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Activity $activity, EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker): JsonResponse
    {
        // Vérification si l'utilisateur est un admin
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Access denied'], 403);
        }

        $em->remove($activity);
        $em->flush();

        return $this->json(['message' => 'Activité supprimée']);
    }

        /**
     * Rechercher des activités avec filtres (prix, date, tag, lieu)
     */
    #[Route('/search', name: 'activity_search', methods: ['GET'])]
    public function search(Request $request, ActivityRepository $repo): JsonResponse
    {
        $filters = [
            'prixMax'   => $request->query->get('prixMax'),
            'date'      => $request->query->get('date'),
            'tag' => $request->query->get('tag'),
            'lieu'      => $request->query->get('lieu'),
        ];

        $results = $repo->findByFilters($filters);

         return $this->render('activity_api/index.html.twig', [
        'activities' => $activities,
    ]);
    }

}