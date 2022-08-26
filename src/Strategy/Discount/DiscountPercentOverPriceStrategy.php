<?php

namespace App\Strategy\Discount;

use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Helper\CalculationHelper;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;

class DiscountPercentOverPriceStrategy implements DiscountStrategyInterface
{
    public function runAlgorithm(DiscountManagerStrategy &$dms)
    {
        $discountDetails = $dms->discountService->getDiscountBy([
            'type' => DiscountType::PERCENT_OVER_PRICE,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();
            if ($dms->orderTotal >= $jsonData['overPrice']) {
                $discountAmount = CalculationHelper::calculatePercent($dms->orderTotal, $jsonData['percent']); //Totalden yüzde alımı
                $dms->discountedTotal = round($dms->discountedTotal - $discountAmount, 2); //Toplam fiyattan düşüş
                $dms->totalDiscount = round($dms->totalDiscount + $discountAmount, 2); //İndirim toplamı arttırma
                $dms->discountMessages[] = [
                    "discountReason" => $dms->discountTypes[DiscountType::PERCENT_OVER_PRICE],
                    "discountAmount" => $discountAmount,
                    "subtotal" => $dms->discountedTotal
                ];
            }
        }
    }
}