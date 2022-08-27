<?php

namespace App\FormRequest;

use App\Entity\OrderProduct;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as AcmeAssert;

class OrderProductUpdateRequest extends BaseRequest
{
    public function getRules(): Assert\Collection
    {
        new AcmeAssert\TableRecordExists(OrderProduct::class, [
            'id' => $this->getRouteParam('orderProductId')
        ]);

        return new Assert\Collection([
            'quantity' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
            ]
        ]);
    }
}