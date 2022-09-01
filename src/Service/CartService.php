<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartProduct;
use App\Entity\Order;
use App\Repository\CartRepository;
use Exception;
use ReflectionException;
use Symfony\Component\Serializer\SerializerInterface;

class CartService extends BaseService
{
    private ProductService $productService;
    private CustomerService $customerService;
    private DiscountService $discountService;

    public function __construct(CartRepository  $repository, SerializerInterface $serializer,
                                ProductService  $productService, CustomerService $customerService,
                                DiscountService $discountService)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;

        $this->productService = $productService;
        $this->customerService = $customerService;
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
    public function getCart(array $criteria, array $orderBy = null, bool $notFoundException = true): ?Order
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
    public function getCartBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Müşterinin sepetindeki tüm ürün bilgilerini döner.
     * @return mixed
     * @throws Exception
     */
    public function index()
    {
        return json_decode($this->serializer->serialize($this->getDefaultCart(), 'json', [
            'groups' => ['cart', 'cartCartProductRelation', 'cartProduct']
        ]));
    }

    /**
     * Sepetdeki ürünlere göre indirimleri hesaplar
     * @return array
     * @throws ReflectionException
     */
    public function discount(): array
    {
        return $this->discountService->getDiscountAnalysis($this);
    }

    /**
     * Müşteriye ait default sepet kaydını döner.
     * @return Cart
     * @throws Exception
     */
    public function getDefaultCart(): Cart
    {
        $firstOrder = $this->repository->getDefaultCart();
        if (is_null($firstOrder)) {
            return $this->store([
                'total' => 0,
                'customer' => $this->customerService->getCustomerTest()
            ]);
        }
        return $firstOrder;
    }

    /**
     * Sepete ürün eklendiğinde, ürün bilgisine göre sepet totalinin artırır.
     * @param CartProduct $cartProduct
     * @return void
     */
    public function updateCartTotalByAddCartProduct(CartProduct $cartProduct): void
    {
        $cart = $cartProduct->getCart();
        $this->update($cart, [
            'total' => ($cart->getTotal() + $cartProduct->getTotal())
        ]);
    }

    /**
     * Sepetdeki ürün güncellendiğinde, ürün bilgisine göre sepet totalinin günceller.
     * @param CartProduct $cartProduct
     * @param int $quantity
     * @return void
     */
    public function updateCartTotalByUpdateCartProduct(CartProduct $cartProduct, int $quantity): void
    {
        $cart = $cartProduct->getCart();
        $product = $cartProduct->getProduct();
        $total = $cart->getTotal() - $cartProduct->getTotal();
        $total = $total + $this->productService->getTotalQuantityPriceByProduct($product, $quantity);
        $this->update($cart, [
            'total' => $total
        ]);
    }

    /**
     * Sepetdeki ürün silindiğinde, ürün bilgisine göre sepet totalini günceller.
     * @param CartProduct $cartProduct
     * @return void
     */
    public function updateCartTotalByDestroyCartProduct(CartProduct $cartProduct): void
    {
        $cart = $cartProduct->getCart();
        $this->update($cart, [
            'total' => $cart->getTotal() - $cartProduct->getTotal()
        ]);
    }
}