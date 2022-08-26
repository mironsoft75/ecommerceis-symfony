<?php

namespace App\Strategy\Discount;

use App\Entity\Order;
use App\Enum\DiscountType;
use App\Helper\ArrayHelper;
use App\Interfaces\Strategy\Discount\DiscountStrategyInterface;
use App\Service\DiscountService;
use App\Service\OrderService;
use Doctrine\Common\Collections\Collection;
use Exception;
use ReflectionException;

class DiscountManagerStrategy
{
    private DiscountStrategyInterface $strategy;

    public OrderService $orderService;
    public DiscountService $discountService;
    public Order $order;
    public Collection $orderProducts;
    public array $discountMessages = [];
    public float $orderTotal; //Siparis toplami
    public float $discountedTotal; //Siparişten indirim düştükten sonra kalan fiyat
    public float $totalDiscount = 0; //Siparişten düşülen toplam indirim
    public array $discountTypes; //Hangi indirim yöntemi ile düşüş yapıldığının bilgisini almak için

    /**
     * @param OrderService $orderService
     * @param DiscountService $discountService
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(OrderService $orderService, DiscountService $discountService)
    {
        $this->orderService = $orderService;
        $this->discountService = $discountService;
        $this->order = $orderService->getDefaultOrder();
        $this->orderProducts = $this->order->getOrderProducts();
        $this->orderTotal = $this->order->getTotal();
        $this->discountedTotal = $this->order->getTotal();
        $this->discountTypes = ArrayHelper::getReflactionClassWithFlip(DiscountType::class);
    }

    /**
     * @param DiscountStrategyInterface $strategy
     * @return void
     */
    public function setStrategy(DiscountStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @return void
     */
    public function runAlgorithm()
    {
        $this->strategy->runAlgorithm($this);
    }

    /**
     * @return array
     */
    public function getAnalysisResult(): array
    {
        return [
            'order_id' => $this->order->getId(),
            'discount' => $this->discountMessages,
            "totalDiscount" => $this->totalDiscount,
            "discountedTotal" => $this->discountedTotal,
            "total" => $this->orderTotal
        ];
    }

    /**
     * @param string $discountReason
     * @param float $discountAmount
     * @return void
     */
    public function addDiscountMessage(string $discountReason, float $discountAmount)
    {
        $this->discountMessages[] = [
            "discountReason" => $discountReason,
            "discountAmount" => $discountAmount,
            "subtotal" => $this->discountedTotal
        ];
    }

    /**
     * @param float $discountAmount
     * @return void
     */
    public function calculateDiscountedTotalAndTotalDiscountByDiscountAmount(float $discountAmount)
    {
        $this->discountedTotal = round($this->discountedTotal - $discountAmount, 2); //Toplam fiyattan düşüş
        $this->totalDiscount = round($this->totalDiscount + $discountAmount, 2); //İndirim toplamı arttırma
    }
}