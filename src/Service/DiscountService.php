<?php

namespace App\Service;

use App\Entity\Discount;
use App\Enum\DiscountType;
use App\Repository\DiscountRepository;
use App\Strategy\Discount\DiscountFreePieceByCategoryAndSoldPieceStrategy;
use App\Strategy\Discount\DiscountManagerStrategy;
use App\Strategy\Discount\DiscountPercentByCategoryAndSoldCheapestStrategy;
use App\Strategy\Discount\DiscountPercentOverPriceStrategy;

class DiscountService extends BaseService
{
    public function __construct(DiscountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Discount|null
     */
    public function getDiscount(array $criteria, array $orderBy = null): ?Discount
    {
        return $this->repository->findOneBy($criteria, $orderBy);
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
     * İndirim algoritmalarına göre sonuçları döndürür.
     * @param OrderService $orderService
     * @return array
     */
    public function getResult(OrderService &$orderService): array
    {
        $order = $orderService->getDefaultOrder();
        $data = (object)[
            'order' => $order, //Sipariş bilgisi
            'orderService' => &$orderService,
            'discountService' => &$this,
            'orderProducts' => $order->getOrderProducts(),  //Siparişe ait ürün Listesi
            'discountMessages' => [],
            'orderTotal' => $order->getTotal(), //Siparis toplami
            'discountedTotal' => $order->getTotal(), //Siparişten indirim düştükten sonra kalan fiyat
            'totalDiscount' => 0, //Siparişten düşülen indirim toplamı
            'discountTypes' => DiscountType::getFlipConstants(), //Hangi indirim yöntemi ile düşüş yapıldığının bilgisini almak için
        ];

        $strategies = [
            new DiscountPercentOverPriceStrategy(), //Belirlenen sipariş toplam sayısına göre X% indirim eklenmesi.
            new DiscountFreePieceByCategoryAndSoldPieceStrategy(), //Belirlenen kategori bilgisine ve satın aldığı adete göre düşülecek olan adet fiyat bilgisini indirime ekler.
            new DiscountPercentByCategoryAndSoldCheapestStrategy() //Belirli kategori ve satış adetine göre en ucuz üründen belirlenen yüzde kadar indirim yapılır.
        ];

        $discountManagerStrategy = new DiscountManagerStrategy();
        foreach ($strategies as $strategy) {
            $discountManagerStrategy->setStrategy($strategy);
            $discountManagerStrategy->algorithm($data);
        }

        return $discountManagerStrategy->getResult();
    }
}