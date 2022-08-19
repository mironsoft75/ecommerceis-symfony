<?php

namespace App\Service;

use App\Entity\Order;
use App\Helper\GeneralHelper;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
//use JMS\Serializer\SerializerInterface;

class OrderService
{
    private $orderRepository, $serializer, $customerRepository;

    public function __construct(OrderRepository $orderRepository, SerializerInterface $serializer, CustomerRepository $customerRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->serializer = $serializer;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function index()
    {
        return json_decode($this->serializer->serialize($this->getDefaultOrder(), 'json', [
            'groups' => ['order', 'orderOrderProductRelation', 'orderProduct', 'orderProductProductRelation', 'product']
        ]));
    }

    public function store()
    {

    }

    /**
     * Müşteriye ait sipariş kaydı varsa döner yoksa oluşturup döner. (FirstOrCreate)
     * @throws NonUniqueResultException
     */
    public function getDefaultOrder(): Order
    {
        $firstOrder = $this->orderRepository->getDefaultOrder();
        if (is_null($firstOrder)) {
            $customer = $this->customerRepository->findOneBy(['id' => GeneralHelper::getCustomerId()]);
            $order = new Order();
            $order->setTotal(0);
            $order->setCustomer($customer);
            $this->orderRepository->add($order, true);
            return $this->getDefaultOrder();
        }
        return $firstOrder;
    }
}