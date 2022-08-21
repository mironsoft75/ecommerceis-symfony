<?php

namespace App\Repository;

use App\Entity\Order;
use App\Helper\GeneralHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Müşteriye ait ilk order kaydını getirir yoksa null döner.
     * @return Order|null
     * @throws NonUniqueResultException
     */
    public function getDefaultOrder(): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.customer = :customer_id')
            ->setParameter('customer_id', getCustomerId())
            ->getQuery()->getOneOrNullResult();
    }
}
