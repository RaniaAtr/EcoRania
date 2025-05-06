<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityController extends AbstractController
{
    #[Route('/activity/api', name: 'app_activity_api')]
    public function index(): Response
    {
        return $this->render('activity_api/index.html.twig', [
            'controller_name' => 'ActivityApiController',
        ]);
    }
}
