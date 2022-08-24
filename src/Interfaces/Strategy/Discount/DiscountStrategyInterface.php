<?php

namespace App\Interfaces\Strategy\Discount;

use App\Service\DiscountService;
use App\Service\OrderService;
use Doctrine\Common\Collections\Collection;

interface DiscountStrategyInterface
{
    /**
     * @param object $data
     * @return void
     */
    public function algorithm(object &$data);
}