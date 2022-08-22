<?php

namespace App\Enums;

use ReflectionClass;

abstract class DiscountType
{
    const PERCENT_OVER_PRICE = 1;
    const CATEGORY_2_SOLD_6_FREE_1 = 2;
    const PERCENT_20_CATEGORY_1_SOLD_2_CHEAPEST = 3;

    private static $constCacheArray = NULL;

    public static function getConstants() {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    public static function getFlipConstants() {
        return array_flip(self::getConstants());
    }
}