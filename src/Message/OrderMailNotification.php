<?php

namespace App\Message;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderProduct;
use Doctrine\Common\Collections\Collection;

class OrderMailNotification
{
    /**
     * @var Order
     */
    private Order $order;
    /**
     * @var Customer
     */
    private Customer $customer;

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->getCustomer();
    }

    /**
     * @return Collection<int, OrderProduct>
     */
    public function getOrderProducts(): Collection
    {
        return $this->order->getOrderProducts();
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->order->getCustomer();
    }

    /**
     * @return string
     */
    public function getCustomerName(): string
    {
        return $this->customer->getName();
    }
}