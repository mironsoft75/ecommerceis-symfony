<?php

namespace App\Strategy\Discount;

use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Helper\CalculationHelper;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;

class DiscountFreePieceByCategoryAndSoldPieceStrategy implements DiscountStrategyInterface
{
    public function algorithm(object &$data)
    {
        $discountDetails = $data->discountService->getDiscountBy([
            'type' => DiscountType::FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();

            foreach ($data->orderProducts as $orderProduct) {
                if ($orderProduct->getProduct()->getCategory()->getId() == $jsonData['categoryId']
                    && $orderProduct->getQuantity() == $jsonData['buyPiece']) {

                    $discountAmount = round($orderProduct->getUnitPrice() * $jsonData['freePiece'], 2);
                    $data->discountedTotal = round($data->discountedTotal - $discountAmount, 2);
                    $data->totalDiscount = round($data->totalDiscount + $discountAmount, 2);

                    $data->discountMessages[] = [
                        "discountReason" => $data->discountTypes[DiscountType::FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE],
                        "discountAmount" => $discountAmount,
                        "subtotal" => $data->discountedTotal
                    ];
                    break;
                }
            }
        }
    }
}