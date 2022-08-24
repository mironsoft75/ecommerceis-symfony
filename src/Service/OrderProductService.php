<?php

namespace App\Service;

use App\Entity\OrderProduct;
use App\Repository\OrderProductRepository;

class OrderProductService extends BaseService
{
    public function __construct(OrderProductRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return OrderProduct|null
     */
    public function getOrderProduct(array $criteria, array $orderBy = null): ?OrderProduct
    {
        return $this->repository->findOneBy($criteria, $orderBy);
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
}