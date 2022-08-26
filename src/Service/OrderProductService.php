<?php

namespace App\Service;

use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Repository\OrderProductRepository;
use Exception;

class OrderProductService extends BaseService
{
    private OrderService $orderService;
    private ProductService $productService;

    public function __construct(OrderProductRepository $repository, OrderService $orderService, ProductService $productService)
    {
        $this->repository = $repository;
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->em = $this->repository->getEntityManager();
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param bool $notFoundException
     * @return OrderProduct|null
     * @throws Exception
     */
    public function getOrderProduct(array $criteria, array $orderBy = null, bool $notFoundException = true): ?OrderProduct
    {
        return $this->findOneBy($criteria, $orderBy, $notFoundException);
    }

    /**
     * @param int $productId
     * @return OrderProduct|null
     * @throws Exception
     */
    public function getOrderProductByProductId(int $productId): ?OrderProduct
    {
        $order = $this->orderService->getDefaultOrder();
        return $this->getOrderProduct([
            'order' => $order->getId(),
            'product' => $productId
        ], null, false);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return OrderProduct[]
     */
    public function getOrderProductBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Siparişe ürünü ekler
     * @param array $attributes
     * @return void
     */
    public function storeOrderProduct(array $attributes)
    {
        $this->em->transactional(function () use ($attributes) {
            $this->sendExceptionBySomeProductIdExists($attributes['product_id']); //aynı ürün daha önceden kayıt oldu mu ?
            $product = $this->productService->getProduct(['id' => $attributes['product_id']]); // ürün mevcut mu ?
            $this->productService->checkStockQuantityByProduct($product, $attributes['quantity']); //ürünün stoğu var mı ?

            $orderProduct = $this->addOrderProduct($product, $attributes['quantity']); //ürünü siparişi kaydet
            $this->orderService->updateOrderTotalByAddOrderProduct($orderProduct); //sipariş totalini güncelle.
        });
    }

    /**
     * Siparişteki ürünü günceller
     * @param int $orderProductId
     * @param array $attributes
     * @return void
     * @throws Exception
     */
    public function updateOrderProduct(array $attributes, int $orderProductId)
    {
        $this->em->transactional(function () use ($orderProductId, $attributes) {
            $orderProduct = $this->getOrderProduct(['id' => $orderProductId]); // OrderProduct mevcut mu ?
            $product = $orderProduct->getProduct(); //ürün bilgiler ulaş
            $this->productService->checkStockQuantityByProduct($product, $attributes['quantity']); //ürün stoğunu kontrol et.

            $this->orderService->updateOrderTotalByUpdateOrderProduct($orderProduct, $attributes['quantity']); //sipariş total inde önceki kayıttaki ürün totalini sil ve yeni ürün totalini güncelle.
            $this->update($orderProduct, [ //OrderProduct bilgilerini güncellenen ürün bilgilerine göre güncelle.
                'quantity' => $attributes['quantity'],
                'unitPrice' => $product->getPrice(),
                'total' => $this->productService->getTotalQuantityPriceByProduct($product, $attributes['quantity'])
            ]);
        });
    }

    /**
     * Siparişten ürünü kaldırır.
     * @param $orderProductId
     * @return void
     */
    public function destroyOrderProduct($orderProductId)
    {
        $this->em->transactional(function () use ($orderProductId) {
            $orderProduct = $this->getOrderProduct(['id' => $orderProductId]); //OrderProduct mevcut mu
            $this->orderService->updateOrderTotalByDestroyOrderProduct($orderProduct); //Sipariş totalinden silencek olan OrderProduct totalini düşür.
            $this->remove($orderProduct); //OrderProduct'dı sil
        });
    }

    /**
     * @param Product $product
     * @param int $quantity
     * @return OrderProduct
     * @throws Exception
     */
    public function addOrderProduct(Product $product, int $quantity): OrderProduct
    {
        return $this->store([
            'order' => $this->orderService->getDefaultOrder(),
            'product' => $product,
            'quantity' => $quantity,
            'unitPrice' => $product->getPrice(),
            'total' => $this->productService->getTotalQuantityPriceByProduct($product, $quantity)
        ]);
    }

    /**
     * OrderProduct'da aynı siparişe ait aynı ürün mevcutsa exception fırlatır.
     * @param int $productId
     * @return void
     * @throws Exception
     */
    public function sendExceptionBySomeProductIdExists(int $productId)
    {
        if(!is_null($this->getOrderProductByProductId($productId))){
            throw new Exception('This record already exists');
        }
    }
}