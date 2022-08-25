<?php

namespace App\FormRequest;

use App\Exception\FormRequestException;
use App\Helper\RequestHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseRequest
{
    private ValidatorInterface $validator;
    private $attributes;
    private $errors;

    /**
     * @throws FormRequestException
     */
    public function __construct(RequestStack $request, ValidatorInterface $validator)
    {
        $this->validator = $validator;

        $this->attributes = RequestHelper::getStackJson($request);
        $this->validate();
        $this->hasError();
    }

    public function validate()
    {
        $this->errors = $this->validator->validate($this->attributes, $this->getRules());
    }

    /**
     * @return Assert\Collection
     */
    public function getRules(): Assert\Collection
    {
        return new Assert\Collection([]);
    }

    /**
     * @throws FormRequestException
     */
    public function hasError()
    {
        if(count($this->errors) > 0){
            throw new FormRequestException($this->errors);
        }
    }

    /**
     * Gets all data
     * @return array
     */
    public function all(): array
    {
        return $this->attributes;
    }
}