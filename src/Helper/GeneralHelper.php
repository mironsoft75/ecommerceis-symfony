<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;

class GeneralHelper
{
    public static function getCustomerId(): int
    {
        return 1;
    }

    public static function getJson(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }

        return $data;
    }

    public static function getErrorMessages(ConstraintViolationList $errors): array
    {
        $messages = [];
        foreach ($errors as $error){
            $messages[$error->getPropertyPath()][] = $error->getMessage();
        }
        return $messages;
    }

    public static function calculatePercent($val, $percent)
    {
        return ($val / 100) * $percent;
    }
}