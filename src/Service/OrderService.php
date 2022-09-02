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
    private ?Order $order;
    private CartService $cartService;

    /**
     * @throws Exception
     */
    public function __construct(OrderRepository $repository, SerializerInterface $serializer,
                                CustomerService $customerService, OrderProductService $orderProductService,
                                CartService     $cartService)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->customerService = $customerService;
        $this->orderProductService = $orderProductService;
        $this->cartService = $cartService;
        $this->em = $this->repository->getEntityManager();
        $this->order = $this->getOrder(['customer' => $this->customerService->getCustomerTest()], null, false);
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
        return json_decode($this->serializer->serialize($this->getDefaultOrder(), 'json', [
            'groups' => ['order', 'orderOrderProductRelation', 'orderProduct']
        ]));
    }

    /**
     * Müşteriye ait default sipariş kaydını döner.
     * @return Order
     * @throws Exception
     */
    public function getDefaultOrder(): Order
    {
        if (is_null($this->order)) {
            $this->order = $this->store([
                'total' => 0,
                'customer' => $this->customerService->getCustomerTest()
            ]);
        }
        return $this->order;
    }

    /**
     * Siparisi kaydini tamamlar
     * @return void
     * @throws Exception
     */
    public function complete()
    {
        $this->em->transactional(function () {
            $cart = $this->cartService->getDefaultCart();
            $order = $this->getDefaultOrder();
            $this->update($cart, [
                'total' => $cart->getTotal()
            ]);

            foreach ($cart->getCartProducts() as $cartProduct) {
                $this->orderProductService->addCartProductToOrderProduct($order, $cartProduct);
            }

            $this->cartService->remove($cart);
        });
    }
}