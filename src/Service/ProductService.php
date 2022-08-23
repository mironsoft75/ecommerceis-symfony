<?php

namespace App\Service;

use App\Repository\ProductRepository;

class ProductService extends BaseService
{
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }
}