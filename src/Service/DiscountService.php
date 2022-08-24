<?php

namespace App\Service;

use App\Entity\Discount;
use App\Entity\Order;
use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Helper\CalculationHelper;
use App\Repository\DiscountRepository;
use App\Strategy\Discount\DiscountFreePieceByCategoryAndSoldPieceStrategy;
use App\Strategy\Discount\DiscountManagerStrategy;
use App\Strategy\Discount\DiscountPercentByCategoryAndSoldCheapestStrategy;
use App\Strategy\Discount\DiscountPercentOverPriceStrategy;
use Doctrine\Common\Collections\Collection;

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
            new DiscountPercentOverPriceStrategy(),
            new DiscountFreePieceByCategoryAndSoldPieceStrategy(),
            new DiscountPercentByCategoryAndSoldCheapestStrategy()
        ];

        $discountManagerStrategy = new DiscountManagerStrategy();
        foreach ($strategies as $strategy){
            $discountManagerStrategy->setStrategy($strategy);
            $discountManagerStrategy->algorithm($data);
        }

        //$this->percentOverPrice(); //Toplam sipariş fiyatlarına göre indirimler yapılır.
        //$this->freePieceByCategoryAndSoldPiece(); //Belirli Kategorideki ve belirli satış adetine göre ücretsiz verilecek adet düşülür
        //$this->percentByCategoryAndSoldCheapest(); //Kategori ve satış adetine göre en ucuz üründen belirlen yüzde kadar indirim yapılır.
//        [
//            'order_id' => $this->order->getId(),
//            'discount' => $this->discountMessages,
//            "totalDiscount" => $this->totalDiscount,
//            "discountedTotal" => $this->discountedTotal,
//            "total" => $this->orderTotal
//        ];

        return $discountManagerStrategy->getResult();
    }

    /**
     * Belirlenen sipariş toplam sayısına göre X% indirim eklenmesi.
     * @return void
     */
    private function percentOverPrice()
    {
        $discountDetails = $this->getDiscountBy([
            'type' => DiscountType::PERCENT_OVER_PRICE,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();
            if ($this->orderTotal >= $jsonData['overPrice']) {
                $discountAmount = CalculationHelper::calculatePercent($this->orderTotal, $jsonData['percent']); //Totalden yüzde alımı
                $this->discountedTotal = round($this->discountedTotal - $discountAmount, 2); //Toplam fiyattan düşüş
                $this->totalDiscount = round($this->totalDiscount + $discountAmount, 2); //İndirim toplamı arttırma
                $this->discountMessages[] = [
                    "discountReason" => $this->discountTypes[DiscountType::PERCENT_OVER_PRICE],
                    "discountAmount" => $discountAmount,
                    "subtotal" => $this->discountedTotal
                ];
            }
        }
    }

    /**
     * Belirlenen kategori bilgisine ve satın aldığı adete göre düşülecek olan adet fiyat bilgisini indirime ekler.
     * @return void
     */
    private function freePieceByCategoryAndSoldPiece()
    {
        $discountDetails = $this->getDiscountBy([
            'type' => DiscountType::FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();

            foreach ($this->orderProducts as $orderProduct) {
                if ($orderProduct->getProduct()->getCategory()->getId() == $jsonData['categoryId']
                    && $orderProduct->getQuantity() == $jsonData['buyPiece']) {

                    $discountAmount = round($orderProduct->getUnitPrice() * $jsonData['freePiece'], 2);
                    $this->discountedTotal = round($this->discountedTotal - $discountAmount, 2);
                    $this->totalDiscount = round($this->totalDiscount + $discountAmount, 2);

                    $this->discountMessages[] = [
                        "discountReason" => $this->discountTypes[DiscountType::FREE_PIECE_BY_CATEGORY_AND_SOLD_PIECE],
                        "discountAmount" => $discountAmount,
                        "subtotal" => $this->discountedTotal
                    ];
                    break;
                }
            }
        }
    }

    /**
     * Belirli kategori ve satış adetine göre en ucuz üründen belirlenen yüzde kadar indirim yapılır.
     * @return void
     */
    public function percentByCategoryAndSoldCheapest()
    {
        $discountDetails = $this->getDiscountBy([
            'type' => DiscountType::PERCENT_CATEGORY_SOLD_CHEAPEST,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();

            $minBuyPrice = 0;
            foreach ($this->orderProducts as $orderProduct) {
                if ($orderProduct->getProduct()->getCategory()->getId() == $jsonData['categoryId'] &&
                    $orderProduct->getQuantity() >= $jsonData['minBuyPiece']) {

                    if ($minBuyPrice == 0) { //Default en dusuk fiyatin belirlenmesi
                        $minBuyPrice = $orderProduct->getUnitPrice();
                    } else if ($minBuyPrice > $orderProduct->getUnitPrice()) { //En dusuk fiyatin bulunmasi
                        $minBuyPrice = $orderProduct->getUnitPrice();
                    }
                }
            }

            if ($minBuyPrice != 0) {
                $discountAmount = round(CalculationHelper::calculatePercent($minBuyPrice, $jsonData['percent']), 2);
                $this->discountedTotal = round($this->discountedTotal - $discountAmount, 2);
                $this->totalDiscount = round($this->totalDiscount + $discountAmount, 2);

                $this->discountMessages[] = [
                    "discountReason" => $this->discountTypes[DiscountType::PERCENT_CATEGORY_SOLD_CHEAPEST],
                    "discountAmount" => $discountAmount,
                    "subtotal" => $this->discountedTotal
                ];
            }
        }
    }

}