<?php

namespace App\Strategy\Discount;

use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;

class DiscountFreePieceByCategoryAndSoldPieceStrategy implements DiscountStrategyInterface
{
    public function runAlgorithm(DiscountManagerStrategy &$dms)
    {
        $discountDetails = $dms->discountService->getDiscountBy([
            'type' => DiscountType::FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();

            foreach ($dms->orderProducts as $orderProduct) {
                if ($orderProduct->getProduct()->getCategory()->getId() == $jsonData['categoryId']
                    && $orderProduct->getQuantity() == $jsonData['buyPiece']) {

                    $discountAmount = round($orderProduct->getUnitPrice() * $jsonData['freePiece'], 2);
                    $dms->discountedTotal = round($dms->discountedTotal - $discountAmount, 2);
                    $dms->totalDiscount = round($dms->totalDiscount + $discountAmount, 2);

                    $dms->discountMessages[] = [
                        "discountReason" => $dms->discountTypes[DiscountType::FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE],
                        "discountAmount" => $discountAmount,
                        "subtotal" => $dms->discountedTotal
                    ];
                    break;
                }
            }
        }
    }
}