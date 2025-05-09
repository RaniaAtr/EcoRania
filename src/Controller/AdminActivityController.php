<?php

namespace App\Controller;


use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/activities')]
class AdminActivityController extends AbstractController
{
    /**
     * GET /api/admin/activities
     * Liste toutes les activités (admin)
     */
    #[Route('', methods: ['GET'])]
    public function index(ActivityRepository $repo): JsonResponse
    {
        return $this->json($repo->findAll());
    }

    /**
     * GET /api/admin/activities/{id}
     * Affiche les détails d’une activité
     */
    #[Route('/{id}', methods: ['GET'])]
    public function show(Activity $activity): JsonResponse
    {
        return $this->json($activity);
    }

    /**
     * POST /api/admin/activities
     * Crée une nouvelle activité
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $activity = new Activity();
        $activity->setTitre($data['titre'] ?? '');
        $activity->setAdresse($data['adresse'] ?? '');

        if (isset($data['date'])) {
            $activity->setDate(new \DateTime($data['date']));
        }

        $activity->setCategorie($data['categorie'] ?? '');
        $activity->setTarif($data['tarif'] ?? 0);
        $activity->setImage($data['image'] ?? '');

        // Durée : on force le suffixe " min" si l'admin envoie juste un nombre
        $duree = isset($data['duree']) ? trim((string)$data['duree']) : '0';
        if (!str_ends_with($duree, 'min')) {
            $duree .= ' min';
        }
        $activity->setDuree($duree);

        $em->persist($activity);
        $em->flush();

        return $this->json($activity, 201);
    }

    /**
     * PUT /api/admin/activities/{id}
     * Modifie une activité existante
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, Activity $activity, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $activity->setTitre($data['titre'] ?? $activity->getTitre());
        $activity->setAdresse($data['adresse'] ?? $activity->getAdresse());

        if (!empty($data['date'])) {
            $activity->setDate(new \DateTime($data['date']));
        }

        $activity->setCategorie($data['categorie'] ?? $activity->getCategorie());
        $activity->setTarif($data['tarif'] ?? $activity->getTarif());
        $activity->setImage($data['image'] ?? $activity->getImage());

        if (isset($data['duree'])) {
            $duree = trim((string)$data['duree']);
            if (!str_ends_with($duree, 'min')) {
                $duree .= ' min';
            }
            $activity->setDuree($duree);
        }

        $em->flush();

        return $this->json($activity);
    }

    /**
     * DELETE /api/admin/activities/{id}
     * Supprime une activité
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Activity $activity, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($activity);
        $em->flush();

        return $this->json(['message' => 'Activité supprimée']);
    }
}

