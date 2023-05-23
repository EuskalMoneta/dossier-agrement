<?php

namespace App\Repository;

use App\Entity\ReductionAdhesion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReductionAdhesion>
 *
 * @method ReductionAdhesion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReductionAdhesion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReductionAdhesion[]    findAll()
 * @method ReductionAdhesion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReductionAdhesionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReductionAdhesion::class);
    }

    public function save(ReductionAdhesion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReductionAdhesion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ReductionAdhesion[] Returns an array of ReductionAdhesion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ReductionAdhesion
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
