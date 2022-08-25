<?php

namespace App\FormRequest;

use Symfony\Component\Validator\Constraints as Assert;

class OrderProductStoreRequest extends BaseRequest
{
    public function getRules(): Assert\Collection
    {
        return new Assert\Collection([
            'product_id' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
            ],
            'quantity' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
            ]
        ]);
    }
}