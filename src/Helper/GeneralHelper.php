<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;

class GeneralHelper
{
    /**
     * @param Request $request
     * @return array
     */
    public static function getJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }

        return $data;
    }

    /**
     * @param RequestStack $request
     * @return array
     */
    public static function getStackJson(RequestStack $request): array
    {
        $data = json_decode($request->getCurrentRequest()->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }

        return $data;
    }

    /**
     * @param ConstraintViolationList $errors
     * @return array
     */
    public static function getErrorMessages(ConstraintViolationList $errors): array
    {
        $messages = [];
        foreach ($errors as $error){
            $messages[$error->getPropertyPath()][] = $error->getMessage();
        }
        return $messages;
    }
}