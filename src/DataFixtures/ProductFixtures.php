<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                "id" => 100,
                "name" => "Black&Decker A7062 40 Parça Cırcırlı Tornavida Seti",
                "category_id" => 1,
                "price" => 120.75,
                "stock" => 10
            ],
            [
                'id' => 101,
                'name' => 'Reko Mini Tamir Hassas Tornavida Seti 32\'li',
                'category_id' => 1,
                'price' => 49.50,
                'stock' => 10,
            ],
            [
                'id' => 102,
                'name' => 'Viko Karre Anahtar - Beyaz',
                'category_id' => 2,
                'price' => 11.28,
                'stock' => 10,
            ],
            [
                'id' => 103,
                'name' => 'Legrand Salbei Anahtar, Alüminyum',
                'category_id' => 2,
                'price' => 22.80,
                'stock' => 10,
            ],
            [
                'id' => 104,
                'name' => 'Schneider Asfora Beyaz Komütatör',
                'category_id' => 2,
                'price' => 12.95,
                'stock' => 10,
            ]
        ];

        $categories = $manager->getRepository(Category::class)->findAll();

        foreach ($data as $item){

            $product = new Product();
            $product->setName($item['name']);
            $product->setCategory($categories[$item['category_id']]);
            $product->setPrice($item['price']);
            $product->setStock($item['stock']);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
