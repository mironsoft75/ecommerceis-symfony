<?php

namespace App\Service;

use App\Repository\OrderRepository;

class OrderService
{
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function index()
    {
        return $this->orderRepository->getAllProductByCustomerId();
    }

    public function store()
    {

    }
}