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

    //    /**
    //     * @return Activity[] Returns an array of Activity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Activity
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }



    public function findByFilters(array $filters): array
{
    $qb = $this->createQueryBuilder('a');

    if (!empty($filters['prixMax'])) {
        $qb->andWhere('a.tarif <= :prixMax')
           ->setParameter('prixMax', $filters['prixMax']);
    }

    if (!empty($filters['date'])) {
        $qb->andWhere('DATE(a.date) = :date')
           ->setParameter('date', new \DateTime($filters['date']));
    }

    if (!empty($filters['tag'])) {
        $qb->andWhere('a.tag LIKE :tag')
           ->setParameter('tag', '%' . $filters['tag'] . '%');
    }

    if (!empty($filters['lieu'])) {
        $qb->andWhere('a.adresse LIKE :lieu')
           ->setParameter('lieu', '%' . $filters['lieu'] . '%');
    }

    return $qb->orderBy('a.date', 'ASC')
              ->getQuery()
              ->getResult();
}

}
