<?php

namespace App\Enum;

use ReflectionClass;

abstract class DiscountType
{
    const PERCENT_OVER_PRICE = 1;
    const FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE = 2;
    const PERCENT_CATEGORY_SOLD_CHEAPEST = 3;

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