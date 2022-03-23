<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return int
     */
    public function getFirstVacantSeat(int $flightId)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT b FROM App\Entity\Booking b WHERE b.flightId=:flight and b.status=:status');
        $query->setParameter('flight', $flightId);
        $query->setParameter('status', Booking::STATUS_VACANT);
        $result = $query->getResult();
        $count = count($result);
        if ($count > 0) {
            $c = rand(0, $count - 1);

            return $result[$c]->getId();
        } else {
            return -1;
        }
    }

    /**
     * @return Booking
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Booking $booking, bool $isNew = false)
    {
        $entityManager = $this->getEntityManager();
        if ($isNew) {
            $entityManager->persist($booking);
        }
        $entityManager->flush();

        return $booking;
    }

    public function getEmailsForFlight(int $flightId)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT DISTINCT u.email FROM App\Entity\Booking b join b.user u WHERE b.flightId=:flight');
        $query->setParameter('flight', $flightId);
        $result = $query->getResult();

        return $result;
    }

    // /**
    //  * @return Booking[] Returns an array of Booking objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
