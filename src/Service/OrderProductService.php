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
}