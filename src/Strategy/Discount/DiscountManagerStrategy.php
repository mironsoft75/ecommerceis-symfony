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

    private OrderService $orderService;
    private DiscountService $discountService;
    private Order $order;
    private Collection $orderProducts;
    private array $discountMessages = [];
    private float $orderTotal; //Siparis toplami
    private float $discountedTotal; //Siparişten indirim düştükten sonra kalan fiyat
    private float $totalDiscount = 0; //Siparişten düşülen toplam indirim
    private array $discountTypes; //Hangi indirim yöntemi ile düşüş yapıldığının bilgisini almak için

    /**
     * @param OrderService $orderService
     * @param DiscountService $discountService
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(OrderService $orderService, DiscountService $discountService)
    {
        $this->setOrderService($orderService);
        $this->setDiscountService($discountService);
        $this->setOrder($this->getOrderService()->getDefaultOrder());
        $this->setOrderProducts($this->getOrder()->getOrderProducts());
        $this->setOrderTotal($this->getOrder()->getTotal());
        $this->setDiscountedTotal($this->getOrder()->getTotal());
        $this->setDiscountTypes(ArrayHelper::getReflactionClassWithFlip(DiscountType::class));
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
        $this->setDiscountedTotal(round($this->getDiscountedTotal() - $discountAmount, 2)); //Toplam fiyattan düşüş
        $this->setTotalDiscount(round($this->getTotalDiscount() + $discountAmount, 2)); //İndirim toplamı arttırma
    }

    /**
     * @return OrderService
     */
    public function getOrderService(): OrderService
    {
        return $this->orderService;
    }

    /**
     * @param OrderService $orderService
     */
    public function setOrderService(OrderService $orderService): void
    {
        $this->orderService = $orderService;
    }

    /**
     * @return DiscountService
     */
    public function getDiscountService(): DiscountService
    {
        return $this->discountService;
    }

    /**
     * @param DiscountService $discountService
     */
    public function setDiscountService(DiscountService $discountService): void
    {
        $this->discountService = $discountService;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    /**
     * @return Collection
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    /**
     * @param Collection $orderProducts
     */
    public function setOrderProducts(Collection $orderProducts): void
    {
        $this->orderProducts = $orderProducts;
    }

    /**
     * @return array
     */
    public function getDiscountMessages(): array
    {
        return $this->discountMessages;
    }

    /**
     * @param array $discountMessages
     */
    public function setDiscountMessages(array $discountMessages): void
    {
        $this->discountMessages = $discountMessages;
    }

    /**
     * @return float
     */
    public function getOrderTotal(): float
    {
        return $this->orderTotal;
    }

    /**
     * @param float $orderTotal
     */
    public function setOrderTotal(float $orderTotal): void
    {
        $this->orderTotal = $orderTotal;
    }

    /**
     * @return float
     */
    public function getDiscountedTotal(): float
    {
        return $this->discountedTotal;
    }

    /**
     * @param float $discountedTotal
     */
    public function setDiscountedTotal(float $discountedTotal): void
    {
        $this->discountedTotal = $discountedTotal;
    }

    /**
     * @return float
     */
    public function getTotalDiscount(): float
    {
        return $this->totalDiscount;
    }

    /**
     * @param float $totalDiscount
     */
    public function setTotalDiscount(float $totalDiscount): void
    {
        $this->totalDiscount = $totalDiscount;
    }

    /**
     * @return array
     */
    public function getDiscountTypes(): array
    {
        return $this->discountTypes;
    }

    /**
     * @param array $discountTypes
     */
    public function setDiscountTypes(array $discountTypes): void
    {
        $this->discountTypes = $discountTypes;
    }
}