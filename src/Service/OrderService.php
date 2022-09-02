<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

class OrderService extends BaseService
{
    private CustomerService $customerService;
    private OrderProductService $orderProductService;
    private CartService $cartService;
    private DiscountService $discountService;

    /**
     * @throws Exception
     */
    public function __construct(OrderRepository $repository, SerializerInterface $serializer,
                                CustomerService $customerService, OrderProductService $orderProductService,
                                CartService     $cartService, DiscountService $discountService)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->customerService = $customerService;
        $this->orderProductService = $orderProductService;
        $this->cartService = $cartService;
        $this->discountService = $discountService;
        $this->em = $this->repository->getEntityManager();
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param bool $notFoundException
     * @return Order|null
     * @throws Exception
     */
    public function getOrder(array $criteria, array $orderBy = null, bool $notFoundException = true): ?Order
    {
        return $this->findOneBy($criteria, $orderBy, $notFoundException);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return Order[]
     */
    public function getOrderBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Müşterinin siparişindeki tüm ürün bilgilerini döner.
     * @return mixed
     * @throws Exception
     */
    public function index()
    {
        return json_decode($this->serializer->serialize($this->repository->findAll(), 'json', [
            'groups' => ['order', 'orderOrderProductRelation', 'orderProduct']
        ]));
    }

    /**
     * Siparisi kaydini tamamlar
     * @param array $attributes
     * @return void
     */
    public function complete(array $attributes)
    {
        $this->em->transactional(function () use ($attributes) {
            $cart = $this->cartService->getDefaultCart();

            $total = $cart->getTotal();
            if (isset($attributes['discount_id'])) { //Indirim mevcut mu ?
                $discountAnalysisWithDiscount = $this->discountService->getDiscountAnalysisWithDiscount($attributes['discount_id']);
                //$discountAnalysisWithDiscount['discount']
                $total = $discountAnalysisWithDiscount['discountAnalysis']['subtotal'];
            }

            //Yeni siparis acilarak sepetin aktarilmasi
            $order = $this->store([
                'total' => $total,
                'customer' => $this->customerService->getCustomerTest()
            ]);

            foreach ($cart->getCartProducts() as $cartProduct) {
                $this->orderProductService->addCartProductToOrderProduct($order, $cartProduct);
            }

            $this->cartService->remove($cart);
        });
    }
}