<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Recherche des activités selon les filtres : prixMax, date, tag, lieu
     *
     * @param array $filters
     * @return Activity[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('a');

        // Filtre prix maximum
        if (!empty($filters['prixMax'])) {
            $qb->andWhere('a.tarif <= :prixMax')
               ->setParameter('prixMax', $filters['prixMax']);
        }

        // Filtre par date (tous les horaires du jour choisi)
        if (!empty($filters['date'])) {
            $date = new \DateTime($filters['date']);
            $start = (clone $date)->setTime(0, 0, 0);
            $end = (clone $date)->setTime(23, 59, 59);

            $qb->andWhere('a.date BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        // Filtre tag (LIKE pour correspondance partielle)
        if (!empty($filters['tag'])) {
            $qb->andWhere('a.tag LIKE :tag')
               ->setParameter('tag', '%' . $filters['tag'] . '%');
        }

        // Filtre lieu (LIKE pour correspondance partielle)
        if (!empty($filters['lieu'])) {
            $qb->andWhere('a.adresse LIKE :lieu')
               ->setParameter('lieu', '%' . $filters['lieu'] . '%');
        }

        // Tri par date croissante
        return $qb->orderBy('a.date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
