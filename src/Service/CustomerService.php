<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Customer|null
     */
    public function getCustomer(array $criteria, array $orderBy = null): ?Customer
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return Customer[]
     */
    public function getCustomerBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }
}