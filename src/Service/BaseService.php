<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseService
{
    protected EntityManagerInterface $em;
    protected SerializerInterface $serializer;
    protected $repository;
    protected string $entityClass;

    public function __construct()
    {
        $this->entityClass = $this->repository->getEntityClass();
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return mixed
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return void
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param $attributes
     * @param bool $flush
     * @return void
     */
    public function store($attributes, bool $flush)
    {
        $entity = new $this->entityClass();
        foreach ($attributes as $key => $attribute) {
            $entity->{'set' . ucfirst($key)}($attribute);
        }
        $this->repository->add($entity, $flush);
    }

    /**
     * @param $entity
     * @param $attributes
     * @param bool $flush
     * @return void
     */
    public function update($entity, $attributes, bool $flush)
    {
        foreach ($attributes as $key => $attribute) {
            $entity->{'set' . ucfirst($key)}($attribute);
        }
        $this->repository->update($entity, $flush);
    }

    /**
     * @param $entity
     * @param bool $flush
     * @return void
     */
    public function remove($entity, bool $flush)
    {
        $this->repository->remove($entity, $flush);
    }
}