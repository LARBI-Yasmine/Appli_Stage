<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

       /**
        *  notifications non lues
        * @return Notification[] Returns an array of Notification 
       */
        public function findUnreadNotifications(): array
        {
            return $this->createQueryBuilder('n')
                ->Where('n.estLu = :estLu')
                ->setParameter('estLu', false)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

    //    public function findOneBySomeField($value): ?Notification
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}