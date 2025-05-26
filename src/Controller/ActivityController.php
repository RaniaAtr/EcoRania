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

    if (isset($data['date'])) {
        $activity->setDate(new \DateTime($data['date']));
    }

    $activity->setCategorie($data['categorie'] ?? '');
    // Assurer que le tarif est bien un nombre
    $tarif = isset($data['tarif']) ? (float) $data['tarif'] : 0;
    $activity->setTarif($tarif);
    $activity->setImage($data['image'] ?? '');

    // Durée : on force le suffixe " min" si l'admin envoie juste un nombre
    $duree = isset($data['duree']) ? trim((string)$data['duree']) : '0';
    if (!str_ends_with($duree, 'min')) {
        $duree .= ' min';
    }
    $activity->setDuree($duree);

    $em->persist($activity);
    $em->flush();

    // Formatage du tarif avec le symbole €
    $formattedActivity = [
        'id' => $activity->getId(),
        'titre' => $activity->getTitre(),
        'adresse' => $activity->getAdresse(),
        'date' => $activity->getDate()->format('Y-m-d H:i:s'),
        'categorie' => $activity->getCategorie(),
        'tarif' => $activity->getTarif() . ' €',  // Ajouter le symbole euro
        'image' => $activity->getImage(),
        'duree' => $activity->getDuree(),
    ];

    // Retourner la réponse avec l'activité formatée
    return $this->json($formattedActivity, 201);
}

    /**
     * Modifier une activité (accessible uniquement pour les administrateurs)
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, Activity $activity, EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker): JsonResponse
    {
        // Vérification si l'utilisateur est un admin
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Access denied'], 403);
        }

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
     * Rechercher des activités avec filtres (prix, date, catégorie, lieu)
     */
    #[Route('/search', name: 'activity_search', methods: ['GET'])]
    public function search(Request $request, ActivityRepository $repo): JsonResponse
    {
        $filters = [
            'prixMax'   => $request->query->get('prixMax'),
            'date'      => $request->query->get('date'),
            'categorie' => $request->query->get('categorie'),
            'lieu'      => $request->query->get('lieu'),
        ];

        $results = $repo->findByFilters($filters);

         return $this->render('activity_api/index.html.twig', [
        'activities' => $activities,
    ]);
    }

}