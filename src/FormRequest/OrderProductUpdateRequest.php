<?php

namespace App\FormRequest;

use Symfony\Component\Validator\Constraints as Assert;

class OrderProductUpdateRequest extends BaseRequest
{
    public function getRules(): Assert\Collection
    {
        return new Assert\Collection([
            'quantity' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
            ]
        ]);
    }
}