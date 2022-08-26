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
     * @param bool $notFoundException
     * @return Order|null
     * @throws Exception
     */
    public function getOrder(array $criteria, array $orderBy = null, bool $notFoundException = true): ?Order
    {
        return $this->findOneBy($criteria, $orderBy, $notFoundException);
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
     * Müşterinin siparişindeki tüm ürün bilgilerini döner.
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
     * Siparişteki ürünlere göre indirimleri hesaplar
     * @return array
     */
    public function discount(): array
    {
        return $this->em->transactional(function (){
            return $this->discountService->getDiscountAnalysis($this);
        });
    }

    /**
     * Müşteriye ait default sipariş kaydını döner.
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
     * Siparişe ürün eklendiğinde, ürün bilgisine göre sipariş totalinin artırır.
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
     * Siparişdeki ürün güncellendiğinde, ürün bilgisine göre sipariş totalinin günceller.
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

    /**
     * Siparişdeki ürün silindiğinde, ürün bilgisine göre sipariş totalini günceller.
     * @param OrderProduct $orderProduct
     * @return void
     */
    public function updateOrderTotalByDestroyOrderProduct(OrderProduct $orderProduct): void
    {
        $order = $orderProduct->getOrder();
        $this->update($order, [
            'total' => $order->getTotal() - $orderProduct->getTotal()
        ]);
    }
}