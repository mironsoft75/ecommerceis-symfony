<?php

namespace App\Service;

use App\Entity\OrderProduct;
use App\Repository\OrderProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderProductService extends BaseService
{
    public function __construct(OrderProductRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * //Örnek Override logic
     * @param $orderProduct
     * @param bool $flush
     * @return void
     */
    public function update($orderProduct, bool $flush)
    {
        $this->update($orderProduct, $flush);
    }
}