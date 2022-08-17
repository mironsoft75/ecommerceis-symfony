<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                "name" => "Türker Jöntürk",
                "since" => "2014-06-28",
                "revenue" => "492.12"
            ],
            [
                "name" => "Kaptan Devopuz",
                "since" => "2015-01-15",
                "revenue" => "1505.95"
            ],
            [
                "name" => "İsa Sonuyumaz",
                "since" => "2016-02-11",
                "revenue" => "0.00"
            ]
        ];

        $customer = null;
        foreach ($data as $item){
            $customer = new Customer();
            $customer->setName($item['name']);
            $customer->setSince(new \DateTime($item['since']));
            $customer->setRevenue($item['revenue']);
            $manager->persist($customer);
        }
        $manager->flush();
    }
}
