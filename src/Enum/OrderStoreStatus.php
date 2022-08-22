<?php

namespace App\Enum;

abstract class OrderStoreStatus
{
    const ERROR = 0;
    const SUCCESS = 1;
    const PRODUCT_STOCK = 2;
}