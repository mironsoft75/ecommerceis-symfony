<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseService
{
    protected EntityManagerInterface $em;
    protected ValidatorInterface $validator;
    protected SerializerInterface $serializer;
}