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
    public function store($attributes, bool $flush = true)
    {
        $entityClass = $this->repository->getClassName();
        $entity = new $entityClass;
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
    public function update($entity, $attributes, bool $flush = true)
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
    public function remove($entity, bool $flush = true)
    {
        $this->repository->remove($entity, $flush);
    }
}