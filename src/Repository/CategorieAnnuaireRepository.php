<?php

namespace App\Repository;

use App\Entity\CategorieAnnuaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieAnnuaire>
 *
 * @method CategorieAnnuaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorieAnnuaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorieAnnuaire[]    findAll()
 * @method CategorieAnnuaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieAnnuaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieAnnuaire::class);
    }

    public function save(CategorieAnnuaire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategorieAnnuaire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CategorieAnnuaire[] Returns an array of CategorieAnnuaire objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CategorieAnnuaire
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
