<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Repository\OrderRepository;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

class OrderService extends BaseService
{
    private ProductService $productService;
    private CustomerService $customerService;
    private DiscountService $discountService;

    public function __construct(OrderRepository $repository, SerializerInterface $serializer,
                                ProductService  $productService, CustomerService $customerService,
                                DiscountService $discountService)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;

        $this->productService = $productService;
        $this->customerService = $customerService;
        $this->discountService = $discountService;

        $this->em = $this->repository->getEntityManager();
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Order
     * @throws Exception
     */
    public function getOrder(array $criteria, array $orderBy = null): Order
    {
        return $this->findOneBy($criteria, $orderBy);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return Order[]
     */
    public function getOrderBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function index()
    {
        return json_decode($this->serializer->serialize($this->getDefaultOrder(), 'json', [
            'groups' => ['order', 'orderOrderProductRelation', 'orderProduct', 'orderProductProductRelation', 'product']
        ]));
    }

    /**
     * @return array
     */
    public function discount(): array
    {
        return $this->em->transactional(function (){
            return $this->discountService->getDiscountAnalysis($this);
        });
    }

    /**
     * @return Order
     * @throws Exception
     */
    public function getDefaultOrder(): Order
    {
        $firstOrder = $this->repository->getDefaultOrder();
        if (is_null($firstOrder)) {
            return $this->store([
                'total' => 0,
                'customer' => $this->customerService->getCustomerTest()
            ]);
        }
        return $firstOrder;
    }

    /**
     * @param OrderProduct $orderProduct
     * @return void
     */
    public function updateOrderTotalByAddOrderProduct(OrderProduct $orderProduct): void
    {
        $order = $orderProduct->getOrder();
        $this->update($order, [
            'total' => ($order->getTotal() + $orderProduct->getTotal())
        ]);
    }

    /**
     * @param OrderProduct $orderProduct
     * @param int $quantity
     * @return void
     */
    public function updateOrderTotalByUpdateOrderProduct(OrderProduct $orderProduct, int $quantity): void
    {
        $order = $orderProduct->getOrder();
        $product = $orderProduct->getProduct();
        $total = $order->getTotal() - $orderProduct->getTotal();
        $total = $total + $this->productService->getTotalQuantityPriceByProduct($product, $quantity);
        $this->update($order, [
            'total' => $total
        ]);
    }

    public function updateOrderTotalByDestroyOrderProduct(OrderProduct $orderProduct): void
    {
        $order = $orderProduct->getOrder();
        $this->update($order, [
            'total' => $order->getTotal() - $orderProduct->getTotal()
        ]);
    }
}