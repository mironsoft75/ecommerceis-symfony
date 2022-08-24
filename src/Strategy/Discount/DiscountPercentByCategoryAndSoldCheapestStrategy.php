<?php

namespace App\Strategy\Discount;

use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Helper\CalculationHelper;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;

class DiscountPercentByCategoryAndSoldCheapestStrategy implements DiscountStrategyInterface
{
    public function algorithm(object &$data)
    {
        $discountDetails = $data->discountService->getDiscountBy([
            'type' => DiscountType::PERCENT_CATEGORY_SOLD_CHEAPEST,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();

            $minBuyPrice = 0;
            foreach ($data->orderProducts as $orderProduct) {
                if ($orderProduct->getProduct()->getCategory()->getId() == $jsonData['categoryId'] &&
                    $orderProduct->getQuantity() >= $jsonData['minBuyPiece']) {

                    if ($minBuyPrice == 0) { //Default en dusuk fiyatin belirlenmesi
                        $minBuyPrice = $orderProduct->getUnitPrice();
                    } else if ($minBuyPrice > $orderProduct->getUnitPrice()) { //En dusuk fiyatin bulunmasi
                        $minBuyPrice = $orderProduct->getUnitPrice();
                    }
                }
            }

            if ($minBuyPrice != 0) {
                $discountAmount = round(CalculationHelper::calculatePercent($minBuyPrice, $jsonData['percent']), 2);
                $data->discountedTotal = round($data->discountedTotal - $discountAmount, 2);
                $data->totalDiscount = round($data->totalDiscount + $discountAmount, 2);

                $data->discountMessages[] = [
                    "discountReason" => $data->discountTypes[DiscountType::PERCENT_CATEGORY_SOLD_CHEAPEST],
                    "discountAmount" => $discountAmount,
                    "subtotal" => $data->discountedTotal
                ];
            }
        }
    }
}