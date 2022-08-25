<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Exception;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Customer
     * @throws Exception
     */
    public function getCustomer(array $criteria, array $orderBy = null): Customer
    {
        return $this->findOneBy($criteria, $orderBy);
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

    /**
     * @return Customer
     * @throws Exception
     */
    public function getCustomerTest(): Customer
    {
        return $this->getCustomer(['id' => getCustomerId()]);
    }
}