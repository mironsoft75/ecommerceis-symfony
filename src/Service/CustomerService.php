<?php

namespace App\Service;

use App\Repository\CustomerRepository;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }
}