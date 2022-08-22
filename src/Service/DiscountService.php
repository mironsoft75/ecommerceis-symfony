<?php

namespace App\Service;

use App\Entity\Order;
use App\Enum\DiscountStatus;
use App\Enum\DiscountType;
use App\Helper\CalculationHelper;
use App\Repository\DiscountRepository;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\Collection;

class DiscountService extends BaseService
{
    private DiscountRepository $discountRepository;
    private OrderRepository $orderRepository;
    private ?Order $order;
    private Collection $orderProducts;
    private array $discountMessages = [];
    private float $orderTotal = 0; //Siparis toplami
    private float $totalDiscount = 0; //Siparişten düşülen indirim toplamı
    private float $discountedTotal = 0; //Siparişten indirimi düştükten sonraki kalan sipariş toplamı
    private array $discountTypes = [];

    public function __construct(DiscountRepository $discountRepository, OrderRepository $orderRepository)
    {
        $this->discountRepository = $discountRepository;
        $this->orderRepository = $orderRepository;
        $this->order = $this->orderRepository->getDefaultOrder(); //Sipariş bilgisi
        $this->orderProducts = $this->order->getOrderProducts(); //Siparişe ait ürün Listesi

        //*Toplam sipariş fiyatından indirimi düştükten sonrası kalan fiyat bilgisi
        //*Güncel toplam sipariş totalinin belirlenmesi
        $this->getOrderTotal();

        //Hangi indirim yöntemi ile düşüş yapıldığının bilgisini almak için
        $this->discountTypes = DiscountType::getFlipConstants();
    }

    /**
     * İndirim algoritmalarına göre sonuçları döndürür.
     * @return array
     */
    public function getResult(): array
    {
        $this->percentOverPrice(); //Toplam sipariş fiyatlarına göre indirimler yapılır.
        $this->freePieceByCategoryAndSoldPiece(); //Belirli Kategorideki ve belirli satış adetine göre ücretsiz verilecek adet düşülür
        $this->percentByCategoryAndSoldCheapest(); //Kategori ve satış adetine göre en ucuz üründen belirlen yüzde kadar indirim yapılır.

        return [
            'order_id' => $this->order->getId(),
            'discount' => $this->discountMessages,
            "totalDiscount" => $this->totalDiscount,
            "discountedTotal" => $this->discountedTotal,
            "total" => $this->orderTotal
        ];
    }

    private function getOrderTotal()
    {
        $this->discountedTotal = 0;
        foreach ($this->orderProducts as $orderProduct) {
            $this->discountedTotal += $orderProduct->getTotal();
            $this->orderTotal += $orderProduct->getTotal();
        }
    }

    /**
     * Belirlenen sipariş toplam sayısına göre X% indirim eklenmesi.
     * @return void
     */
    private function percentOverPrice()
    {
        $discountDetails = $this->discountRepository->findBy([
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
        $discountDetails = $this->discountRepository->findBy([
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
        $discountDetails = $this->discountRepository->findBy([
            'type' => DiscountType::PERCENT_CATEGORY_SOLD_CHEAPEST,
            'status' => DiscountStatus::ACTIVE
        ]);

        foreach ($discountDetails as $discountDetail) {
            $jsonData = $discountDetail->getJsonData();

            $minBuyPrice = 0;
            foreach ($this->orderProducts as $orderProduct) {
                if ($orderProduct->getProduct()->getCategory()->getId() == $jsonData['categoryId'] &&
                    $orderProduct->getQuantity() >= $jsonData['minBuyPiece']) {

                    dump($orderProduct->getUnitPrice());
                    if ($minBuyPrice == 0) { //default min product price
                        $minBuyPrice = $orderProduct->getUnitPrice();
                    } else if ($minBuyPrice > $orderProduct->getUnitPrice()) { //detect min price
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