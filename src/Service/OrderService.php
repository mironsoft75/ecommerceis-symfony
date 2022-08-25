<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Enum\OrderStoreStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class OrderService extends BaseService
{
    private OrderProductService $orderProductService;
    private ProductService $productService;
    private CustomerService $customerService;
    private DiscountService $discountService;

    public function __construct(OrderRepository        $repository, SerializerInterface $serializer,
                                OrderProductService    $orderProductService, ProductService $productService,
                                CustomerService        $customerService, DiscountService $discountService,
                                EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->em = $em;

        $this->orderProductService = $orderProductService;
        $this->productService = $productService;
        $this->customerService = $customerService;
        $this->discountService = $discountService;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Order|null
     */
    public function getOrder(array $criteria, array $orderBy = null): ?Order
    {
        return $this->repository->findOneBy($criteria, $orderBy);
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
     * Siparişe ait tüm ürünleri listeleme
     * @return mixed
     */
    public function index()
    {
        return json_decode($this->serializer->serialize($this->getDefaultOrder(), 'json', [
            'groups' => ['order', 'orderOrderProductRelation', 'orderProduct', 'orderProductProductRelation', 'product']
        ]));
    }

    /**
     * Siparişe ürün ekleme veya mevcut ürünü güncelleme.
     * @param array $attributes
     * @return mixed
     */
    public function storeProduct(array $attributes)
    {
        return $this->em->transactional(function () use ($attributes) {
            $order = $this->getDefaultOrder();

            $product = $this->productService->getProduct([
                'id' => $attributes['product_id']
            ]);

            if ($order && $product) {
                if ($attributes['quantity'] > $product->getStock()) {
                    return OrderStoreStatus::PRODUCT_STOCK;
                }

                $orderProduct = $this->orderProductService->getOrderProduct( //aynı ürün daha önce eklenmiş mi ?
                    [
                        'order' => $order->getId(),
                        'product' => $product->getId()
                    ]
                );

                if ($orderProduct) { //aynı ürün daha önce eklenmişse güncelleme yapılır.
                    $productTotal = $this->updateOrderTotalByOrderProduct($order, $orderProduct, $product, $attributes['quantity']);
                    $this->orderProductService->update($orderProduct, [
                        'quantity' => $attributes['quantity'],
                        'unitPrice' => $product->getPrice(),
                        'total' => $productTotal
                    ]);
                } else { //aynı ürün daha önce siparişe eklenmediyse ürünü siparişe ekleme yapılır
                    $productTotal = $product->getPrice() * $attributes['quantity']; // ürünün toplam fiyatı.
                    $this->orderProductService->store([
                        'order' => $order,
                        'product' => $product,
                        'quantity' => $attributes['quantity'],
                        'unitPrice' => $product->getPrice(),
                        'total' => $productTotal
                    ]);
                    $order->setTotal($order->getTotal() + $productTotal); //Order Total bilgisinin güncellenmesi
                }

                $this->update($order); //ürün toplam fiyatının güncellenmesi.
                return OrderStoreStatus::SUCCESS;
            }
            return OrderStoreStatus::ERROR;
        });
    }

    /**
     * OrderProduct da göre order total bilgisini atar ve ürün fiyatını döner.
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param Product $product
     * @param int $quantity
     * @return float
     */
    public function updateOrderTotalByOrderProduct(Order &$order, OrderProduct &$orderProduct, Product &$product, int &$quantity): float
    {
        $productTotal = $product->getPrice() * $quantity;
        $order->setTotal($order->getTotal() - $orderProduct->getTotal()); //mevcut OrderProduct ın totalinin Order total den düşümü
        $order->setTotal($order->getTotal() + $productTotal); //güncel order totalin atanması.
        return $productTotal;
    }

    /**
     * Siparişten ürünü siler ve sipariş total fiyatını günceller.
     * @param $productId
     * @return bool
     */
    public function removeByProductId($productId): bool
    {
        return $this->em->transactional(function () use ($productId) {
            $order = $this->getDefaultOrder();

            $orderProduct = $this->orderProductService->getOrderProduct([
                'order' => $order->getId(),
                'product' => $productId
            ]);

            if ($orderProduct) {
                $this->update($order, [
                    'total' => $order->getTotal() - $orderProduct->getTotal()
                ]);
                $this->orderProductService->remove($orderProduct);
                return true;
            }
            return false;
        });
    }

    /**
     * Sipariş bilgilerine göre indirimleri hesaplar
     * @return array
     */
    public function discount(): array
    {
        return $this->discountService->getDiscountAnalysis($this);
    }

    /**
     * Müşteriye ait sipariş kaydı varsa döner yoksa oluşturup döner. (FirstOrCreate)
     */
    public function getDefaultOrder(): Order
    {
        return $this->em->transactional(function () {
            $firstOrder = $this->repository->getDefaultOrder();
            if (is_null($firstOrder)) {
                $customer = $this->customerService->getCustomer(['id' => getCustomerId()]);
                $this->store([
                    'total' => 0,
                    'customer' => $customer
                ]);
                return $this->getDefaultOrder();
            }
            return $firstOrder;
        });
    }
}