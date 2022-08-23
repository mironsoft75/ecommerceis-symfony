<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Enum\OrderStoreStatus;
use App\Helper\GeneralHelper;
use App\Repository\CustomerRepository;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

            $product = $this->productService->findOneBy([
                'id' => $attributes['product_id']
            ]);

            if ($order && $product) {
                if ($attributes['quantity'] > $product->getStock()) {
                    return OrderStoreStatus::PRODUCT_STOCK;
                }

                $productTotal = $product->getPrice() * $attributes['quantity']; // ürünün toplam fıyatı.

                $orderProduct = $this->orderProductService->findOneBy( //aynı ürün daha önce eklenmiş mi ?
                    [
                        'order' => $order->getId(),
                        'product' => $product->getId()
                    ]
                );

                //TODO: fonksiyona cevrilecek
                if ($orderProduct) { //aynı ürün daha önce eklenmişse güncelleme yapılır.
                    $orderProduct->setQuantity($attributes['quantity']);
                    $orderProduct->setUnitPrice($product->getPrice());
                    $orderProduct->setTotal($productTotal);
                    $this->orderProductService->update($orderProduct, true);
                } else { //aynı ürün daha önce siparişe eklenmediyse ürünü siparişe ekleme yapılır
                    $orderProduct = new OrderProduct();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($product);
                    $orderProduct->setQuantity($attributes['quantity']);
                    $orderProduct->setUnitPrice($product->getPrice());
                    $orderProduct->setTotal($productTotal);
                    $this->orderProductService->store($orderProduct, true);
                }

                //TODO: quantity degistiginde total guncellenecek
                $order->setTotal($order->getTotal() + $productTotal); //Order Total bilgisinin güncellenmesi
                $this->update($order, true); //ürün toplam fiyatının güncellenmesi.
                return OrderStoreStatus::SUCCESS;
            }
            return OrderStoreStatus::ERROR;
        });
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

            $orderProduct = $this->orderProductService->findOneBy([
                'order' => $order->getId(),
                'product' => $productId
            ]);

            if ($orderProduct) {
                $order->setTotal($order->getTotal() - $orderProduct->getTotal());
                $this->update($order, true);
                $this->orderProductService->remove($orderProduct, true);
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
        return $this->discountService->getResult();
    }

    /**
     * Müşteriye ait sipariş kaydı varsa döner yoksa oluşturup döner. (FirstOrCreate)
     */
    public function getDefaultOrder(): Order
    {
        return $this->em->transactional(function () {
            $firstOrder = $this->repository->getDefaultOrder();
            if (is_null($firstOrder)) {
                $customer = $this->customerService->findOneBy(['id' => getCustomerId()]);
                $order = new Order();
                $order->setTotal(0);
                $order->setCustomer($customer);
                $this->repository->add($order, true);
                return $this->getDefaultOrder();
            }
            return $firstOrder;
        });
    }
}