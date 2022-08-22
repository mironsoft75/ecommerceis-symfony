<?php

namespace App\Service;

use App\Entity\Order;
use App\Enums\DiscountType;
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
        $this->percentOverPrice();

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
        foreach ($this->orderProducts as $orderProduct){
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
            'type' => DiscountType::PERCENT_OVER_PRICE
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

}