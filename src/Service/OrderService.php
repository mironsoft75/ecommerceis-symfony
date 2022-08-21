<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Enums\OrderStoreStatus;
use App\Helper\GeneralHelper;
use App\Repository\CustomerRepository;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Serializer\SerializerInterface;


class OrderService
{
    private $orderRepository, $customerRepository, $productRepository, $orderProductRepository, $serializer;

    public function __construct(OrderRepository     $orderRepository, CustomerRepository $customerRepository,
                                ProductRepository   $productRepository, OrderProductRepository $orderProductRepository,
                                SerializerInterface $serializer)
    {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->orderProductRepository = $orderProductRepository;
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

    /**
     * @param $attributes
     * @return int
     * @throws NonUniqueResultException
     */
    public function store($attributes): int
    {
        $order = $this->getDefaultOrder();
        $product = $this->productRepository->findOneBy([
            'id' => $attributes['product_id']
        ]);

        if ($order && $product) {
            if ($attributes['quantity'] > $product->getStock()) {
                return OrderStoreStatus::PRODUCT_STOCK;
            }

            $total = $product->getPrice() * $attributes['quantity']; // ürünün toplam fıyatı.

            $orderProduct = $this->orderProductRepository->findOneBy( //aynı ürün daha önce eklenmiş mi ?
                [
                    'order' => $order->getId(),
                    'product' => $product->getId()
                ]
            );

            if ($orderProduct) { //aynı ürün daha önce eklenmişse güncelleme yapılır.
                $orderProduct->setQuantity($attributes['quantity']);
                $orderProduct->setUnitPrice($product->getPrice());
                $orderProduct->setTotal($total);
                $this->orderProductRepository->update($orderProduct, true);
            } else { //aynı ürün daha önce siparişe eklenmediyse ürünü siparişe ekleme yapılır
                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($attributes['quantity']);
                $orderProduct->setUnitPrice($product->getPrice());
                $orderProduct->setTotal($total);
                $this->orderProductRepository->add($orderProduct, true);
            }

            //Siparis toplaminin guncellenmesi
            $orderTotal = 0;
            foreach ($order->getOrderProducts() as $orderProduct) {
                $orderTotal += $orderProduct->getTotal();
            }

            $order->setTotal($orderTotal);
            $this->orderRepository->update($order, true);

            return OrderStoreStatus::SUCCESS;
        }
        return OrderStoreStatus::ERROR;
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