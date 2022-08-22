<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Enums\OrderStoreStatus;
use App\Helper\GeneralHelper;
use App\Repository\CustomerRepository;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderService extends BaseService
{
    private OrderRepository $orderRepository;
    private CustomerRepository $customerRepository;
    private ProductRepository $productRepository;
    private OrderProductRepository $orderProductRepository;

    public function __construct(OrderRepository        $orderRepository, CustomerRepository $customerRepository,
                                ProductRepository      $productRepository, OrderProductRepository $orderProductRepository,
                                SerializerInterface    $serializer, ValidatorInterface $validator,
                                EntityManagerInterface $em)
    {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->orderProductRepository = $orderProductRepository;

        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;

        $this->orderRepository->setEntityManager($this->em);
        $this->customerRepository->setEntityManager($this->em);
        $this->productRepository->setEntityManager($this->em);
        $this->orderProductRepository->setEntityManager($this->em);

    }

    /**
     * Siparişe ait tüm ürünleri listeleme
     * @return mixed
     */
    public function index()
    {
        return json_decode($this->serializer->serialize($this->getDefaultOrder(), 'json', [
            'groups' => ['order', 'orderOrderProductRelation', 'orderProduct', 'orderProductProductRelation', 'product']
        ]));
    }

    /**
     * Siparişe ürün ekleme veya mevcut ürünü güncelleme.
     * @param $attributes
     * @return mixed
     */
    public function store($attributes)
    {
        return $this->em->transactional(function ($em) use ($attributes) {

            $errors = $this->validator->validate($attributes, new Assert\Collection([
                'product_id' => [
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                ],
                'quantity' => [
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                ]
            ]));

            if (count($errors) > 0) {
                return GeneralHelper::getErrorMessages($errors);
            }

            $order = $this->getDefaultOrder();
            $product = $this->productRepository->findOneBy([
                'id' => $attributes['product_id']
            ]);

            if ($order && $product) {
                if ($attributes['quantity'] > $product->getStock()) {
                    return OrderStoreStatus::PRODUCT_STOCK;
                }

                $total = $product->getPrice() * $attributes['quantity']; // ürünün toplam fıyatı.

                $orderProduct = $this->orderProductRepository->findOneBy( //aynı ürün daha önce eklenmiş mi ?
                    [
                        'order' => $order->getId(),
                        'product' => $product->getId()
                    ]
                );

                if ($orderProduct) { //aynı ürün daha önce eklenmişse güncelleme yapılır.
                    $orderProduct->setQuantity($attributes['quantity']);
                    $orderProduct->setUnitPrice($product->getPrice());
                    $orderProduct->setTotal($total);
                    $this->orderProductRepository->update($orderProduct, true);
                } else { //aynı ürün daha önce siparişe eklenmediyse ürünü siparişe ekleme yapılır
                    $orderProduct = new OrderProduct();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($product);
                    $orderProduct->setQuantity($attributes['quantity']);
                    $orderProduct->setUnitPrice($product->getPrice());
                    $orderProduct->setTotal($total);
                    $this->orderProductRepository->add($orderProduct, true);
                }

                $this->updateOrderTotal($order);
                return OrderStoreStatus::SUCCESS;
            }
            return OrderStoreStatus::ERROR;
        });
    }

    /**
     * Siparişten ürünü siler ve sipariş total fiyatını günceller.
     * @param $productId
     * @return bool
     */
    public function removeByProductId($productId): bool
    {
        return $this->em->transactional(function ($em) use ($productId) {
            $order = $this->getDefaultOrder();
            $orderProduct = $this->orderProductRepository->findOneBy([
                'order' => $order->getId(),
                'product' => $productId
            ]);

            if ($orderProduct) {
                $this->orderProductRepository->remove($orderProduct, true);
                $this->updateOrderTotal($order);
                return true;
            }
            return false;
        });
    }

    public function discount(): array
    {
        /**
         * Buradaki her algoritma metotlara ayrılabilir ben bilerek ayırmadım
         * Metotlara ayrıldığında tüm kontroller için daha fazla çok döngüye sahip olmuş oluyor buda daha fazla işlem
         * yapacağı anlamına geliyor. Tercihler kişiye göre değişebilir burada performans odaklı ilerlenmiştir
         */

        $order = $this->repository->getDefaultOrder();
        $discountedTotal = $order->total;
        $totalDiscount = 0;
        $discount = [];

        $total1000 = 0;
        $justOneTimeCategory2Sold6Free1Status = false;
        $category1Sold2Count = 0;
        $category1Sold2CheapestPrice = 0;
        foreach ($order->products as $product) {

            //10_PERCENT_OVER_1000
            $total1000 += $product->pivot->total;

            //CATEGORY_2_SOLD_6_FREE_1
            if ($justOneTimeCategory2Sold6Free1Status === false && $product->category_id == 2) {
                if ($product->pivot->quantity === 6) { //6 adet alındığından dendiğinden dolayı sadece 6 adet.
                    $justOneTimeCategory2Sold6Free1Status = true;
                    $discountedTotal = round($discountedTotal - $product->pivot->unit_price);
                    $discountAmount = round($product->pivot->unit_price, 2);
                    $discount[] = [
                        "discountReason" => "CATEGORY_2_SOLD_6_FREE_1",
                        "discountAmount" => $discountAmount,
                        "subtotal" => $discountedTotal
                    ];

                    $totalDiscount = round($totalDiscount + $discountAmount, 2);
                }
            }

            //CATEGORY_1_SOLD_2_CHEAPEST
            if ($product->category_id == 1) {
                if ($category1Sold2CheapestPrice == 0) { //set default min price
                    $category1Sold2CheapestPrice = $product->pivot->unit_price;
                } else if ($category1Sold2CheapestPrice > $product->pivot->unit_price) { //detect min price
                    $category1Sold2CheapestPrice = $product->pivot->unit_price;
                }
                $category1Sold2Count++;
            }
        }

        //10_PERCENT_OVER_1000 //Toplam sipariş bilgisinden de kontrol edilebilirdi ancak hesaplama istendiği için eklendi.
        if ($total1000 >= 1000) {
            $percent = round(calculatePercent($discountedTotal, 10), 2);
            $discountedTotal = round($discountedTotal - $percent, 2);
            $discountAmount = $percent;
            $discount[] = [
                "discountReason" => "10_PERCENT_OVER_1000",
                "discountAmount" => $discountAmount,
                "subtotal" => $discountedTotal
            ];

            $totalDiscount = round($totalDiscount + $discountAmount, 2);
        }

        //CATEGORY_1_SOLD_2_CHEAPEST
        if ($category1Sold2Count >= 2) {
            $percent = round(calculatePercent($category1Sold2CheapestPrice, 20), 2);
            $discountedTotal = round($discountedTotal - $percent, 2);
            $discountAmount = $percent;
            $discount[] = [
                "discountReason" => "CATEGORY_1_SOLD_2_CHEAPEST",
                "discountAmount" => $discountAmount,
                "subtotal" => $discountedTotal
            ];

            $totalDiscount = round($totalDiscount + $discountAmount, 2);
        }

        return [
            'order_id' => $order->id,
            'discount' => $discount,
            "totalDiscount" => $totalDiscount,
            "discountedTotal" => $discountedTotal
        ];
    }

    /**
     * Müşteriye ait sipariş kaydı varsa döner yoksa oluşturup döner. (FirstOrCreate)
     */
    public function getDefaultOrder(): Order
    {
        return $this->em->transactional(function ($em) {
            $firstOrder = $this->orderRepository->getDefaultOrder();
            if (is_null($firstOrder)) {
                $customer = $this->customerRepository->findOneBy(['id' => getCustomerId()]);
                $order = new Order();
                $order->setTotal(0);
                $order->setCustomer($customer);
                $this->orderRepository->add($order, true);
                return $this->getDefaultOrder();
            }
            return $firstOrder;
        });
    }

    /**
     * Siparişin toplam bilgisini günceller
     * @param Order|null $order
     * @return void
     */
    public function updateOrderTotal(Order $order = null)
    {
        $order = $order ?? $this->getDefaultOrder();
        $orderTotal = 0;
        foreach ($order->getOrderProducts() as $orderProduct) {
            $orderTotal += $orderProduct->getTotal();
        }

        $order->setTotal($orderTotal);
        $this->orderRepository->update($order, true);
    }
}