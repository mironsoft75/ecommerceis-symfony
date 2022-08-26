<?php

namespace App\Service;

use App\Entity\Discount;
use App\Repository\DiscountRepository;
use App\Strategy\Discount\DiscountFreePieceByCategoryAndSoldPieceStrategy;
use App\Strategy\Discount\DiscountManagerStrategy;
use App\Strategy\Discount\DiscountPercentByCategoryAndSoldCheapestStrategy;
use App\Strategy\Discount\DiscountPercentOverPriceStrategy;
use Exception;
use ReflectionException;

class DiscountService extends BaseService
{
    public function __construct(DiscountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Discount
     * @throws Exception
     */
    public function getDiscount(array $criteria, array $orderBy = null): Discount
    {
        return $this->findOneBy($criteria, $orderBy);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return Discount[]
     */
    public function getDiscountBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param OrderService $orderService
     * @return array
     * @throws ReflectionException
     */
    public function getDiscountAnalysis(OrderService $orderService): array
    {
        $strategies = [
            new DiscountPercentOverPriceStrategy(), //Belirlenen sipariş toplam sayısına göre X% indirim eklenmesi.
            new DiscountFreePieceByCategoryAndSoldPieceStrategy(), //Belirlenen kategori bilgisine ve satın aldığı adete göre düşülecek olan adet fiyat bilgisini indirime ekler.
            new DiscountPercentByCategoryAndSoldCheapestStrategy() //Belirli kategori ve satış adetine göre en ucuz üründen belirlenen yüzde kadar indirim yapılır.
        ];

        $discountManagerStrategy = new DiscountManagerStrategy($orderService, $this);
        foreach ($strategies as $strategy) {
            $discountManagerStrategy->setStrategy($strategy);
            $discountManagerStrategy->runAlgorithm();
        }

        return $discountManagerStrategy->getAnalysisResult();
    }
}