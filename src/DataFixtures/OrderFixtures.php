<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'quantity' => 10,
            ],
            [
                'quantity' => 2,
            ],
            [
                'quantity' => 1,
            ],
            [
                'quantity' => 6,
            ],
            [
                'quantity' => 10,
            ]
        ];
        $dataCount = count($data) - 1;

        $products = $manager->getRepository(Product::class)->findAll();
        $productCount = count($products) - 1;
        $customers = $manager->getRepository(Customer::class)->findAll();

        foreach ($customers as $customer){
            $orderTotal = 0;
            $order = new Order(); //siparis
            $order->setCustomer($customer);
            $order->setTotal($orderTotal);
            $manager->persist($order);
            $purchaseCount = rand(1,4); //siparese kac adet urun eklenecek
            for($i = 0; $i < $purchaseCount; $i++){
                $randomData = $data[rand(0, $dataCount)];
                $randomProduct = $products[rand(0, $productCount)];
                $total = $randomProduct->getPrice() * $randomData['quantity'];
                $orderTotal += $total;

                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($randomProduct);
                $orderProduct->setQuantity($randomData['quantity']);
                $orderProduct->setUnitPrice($randomProduct->getPrice());
                $orderProduct->setTotal($total);
                $manager->persist($orderProduct);
            }
            $order->setTotal($orderTotal); //Urun totalinin siparise yansitilmasi
            $manager->persist($order);
        }

        $manager->flush();
    }
}
