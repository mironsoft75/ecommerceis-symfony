<?php

namespace App\Strategy\Discount;

use App\Entity\Order;
use App\Enum\DiscountType;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;
use App\Service\DiscountService;
use App\Service\OrderService;
use Doctrine\Common\Collections\Collection;

class DiscountManagerStrategy
{
    private ?DiscountStrategyInterface $strategy;
    private object $data;

    /**
     * @param DiscountStrategyInterface|null $strategy
     */
    public function __construct(DiscountStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy;
    }

    /**
     * @param DiscountStrategyInterface $strategy
     * @return void
     */
    public function setStrategy(DiscountStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @return void
     */
    public function algorithm(&$data)
    {
        $this->data = $data;
        $this->strategy->algorithm($data);
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return [
            'order_id' => $this->data->order->getId(),
            'discount' => $this->data->discountMessages,
            "totalDiscount" => $this->data->totalDiscount,
            "discountedTotal" => $this->data->discountedTotal,
            "total" => $this->data->orderTotal
        ];
    }
}