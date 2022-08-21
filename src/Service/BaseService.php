<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseService
{
    protected EntityManagerInterface $entityManager;
    protected ValidatorInterface $validator;
    protected SerializerInterface $serializer;
}