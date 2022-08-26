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
     * @return OrderProduct
     * @throws Exception
     */
    public function getOrderProduct(array $criteria, array $orderBy = null, bool $notFoundException = true): OrderProduct
    {
        return $this->findOneBy($criteria, $orderBy, $notFoundException);
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
     * @param array $attributes
     * @return void
     */
    public function storeOrderProduct(array $attributes)
    {
        $this->em->transactional(function () use ($attributes) {
            $product = $this->productService->getProduct(['id' => $attributes['product_id']]);
            $this->productService->checkStockQuantityByProduct($product, $attributes['quantity']);

            $orderProduct = $this->addOrderProduct($product, $attributes['quantity']);
            $this->orderService->updateOrderTotalByAddOrderProduct($orderProduct);
        });
    }

    /**
     * @param int $orderProductId
     * @param array $attributes
     * @return void
     * @throws Exception
     */
    public function updateOrderProduct(array $attributes, int $orderProductId)
    {
        $this->em->transactional(function () use ($orderProductId, $attributes) {
            $orderProduct = $this->getOrderProduct(['id' => $orderProductId]);
            $product = $orderProduct->getProduct();
            $this->productService->checkStockQuantityByProduct($product, $attributes['quantity']);

            $this->orderService->updateOrderTotalByUpdateOrderProduct($orderProduct, $attributes['quantity']);
            $this->update($orderProduct, [
                'quantity' => $attributes['quantity'],
                'unitPrice' => $product->getPrice(),
                'total' => $this->productService->getTotalQuantityPriceByProduct($product, $attributes['quantity'])
            ]);
        });
    }

    /**
     * @param $orderProductId
     * @return void
     */
    public function destroyOrderProduct($orderProductId)
    {
        $this->em->transactional(function () use ($orderProductId) {
            $orderProduct = $this->getOrderProduct(['id' => $orderProductId]);
            $this->orderService->updateOrderTotalByDestroyOrderProduct($orderProduct);
            $this->remove($orderProduct);
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
}