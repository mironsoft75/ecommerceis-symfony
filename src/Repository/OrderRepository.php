<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Helper\GeneralHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function add(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getDefaultOrder(): Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.customer = :customer_id')
            ->setParameter('customer_id', GeneralHelper::getCustomerId())
            //->setMaxResults(1)
            ->getQuery()->getSingleResult();

        /*return $this->findBy([
            'customer' => GeneralHelper::getCustomerId()
        ]);*/
    }

    public function getOrderProducts(Order $order)
    {
        /*foreach (as $product)
        {
            dd($product->getProduct());
        }*/

        $test = $order->getOrderProducts();
        dd($test);
        //dd($test->getProduct());
        return $order;
    }

    public function getAllProductByCustomerId()
    {
        dd($this->getOrderProducts($this->getDefaultOrder()));
        //dd();

        /*$this->getDefaultOrder()->getProducts()
        foreach ( as $product){
            dd($product);
        }*/

        //$order->getProducts();

        /*return $this->createQueryBuilder('o')
            ->select('o.id, o.total, p.quantity')
            ->join('o.products', 'p')
            //->join('p.order', 'po')
            ->where('o.customer = :customer_id')
            ->setParameter('customer_id', GeneralHelper::getCustomerId())
            ->getQuery()->getResult();*/
    }

//    /**
//     * @return Order[] Returns an array of Order objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Order
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
