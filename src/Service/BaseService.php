<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class BaseService
{
    protected EntityManagerInterface $em;
    protected SerializerInterface $serializer;
    protected $repository;

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
     * @param array $attributes
     * @param bool $flush
     * @return void
     */
    public function update($entity, array $attributes = [], bool $flush = true)
    {
        if (count($attributes) > 0) {
            foreach ($attributes as $key => $attribute) {
                $entity->{'set' . ucfirst($key)}($attribute);
            }
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