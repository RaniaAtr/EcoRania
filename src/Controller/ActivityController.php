<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/activities')]
class ActivityController extends AbstractController
{
    /**
     
     * Liste toutes les activités disponibles
     */
    #[Route('', methods: ['GET'])]
    public function index(ActivityRepository $repo): JsonResponse
    {
        return $this->json($repo->findAll());
    }

    /**
   
     * Détail d'une activité
     */
    #[Route('/{id}', methods: ['GET'])]
    public function show(Activity $activity): JsonResponse
    {
        return $this->json($activity);
    }
}
