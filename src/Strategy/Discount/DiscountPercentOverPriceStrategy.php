<?php

namespace App\Strategy\Discount;

use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Helper\CalculationHelper;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;

class DiscountPercentOverPriceStrategy implements DiscountStrategyInterface
{
    public function algorithm(object &$data)
    {
        $discountDetails = $data->discountService->getDiscountBy([
            'type' => DiscountType::PERCENT_OVER_PRICE,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();
            if ($data->orderTotal >= $jsonData['overPrice']) {
                $discountAmount = CalculationHelper::calculatePercent($data->orderTotal, $jsonData['percent']); //Totalden yüzde alımı
                $data->discountedTotal = round($data->discountedTotal - $discountAmount, 2); //Toplam fiyattan düşüş
                $data->totalDiscount = round($data->totalDiscount + $discountAmount, 2); //İndirim toplamı arttırma
                $data->discountMessages[] = [
                    "discountReason" => $data->discountTypes[DiscountType::PERCENT_OVER_PRICE],
                    "discountAmount" => $discountAmount,
                    "subtotal" => $data->discountedTotal
                ];
            }
        }
    }
}