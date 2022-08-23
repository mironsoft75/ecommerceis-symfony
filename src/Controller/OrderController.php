<?php

namespace App\Controller;

use App\Enum\OrderStoreStatus;
use App\Helper\GeneralHelper;
use App\Helper\RedirectHelper;
use App\Service\OrderService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrderController extends AbstractController
{
    private OrderService $orderService;
    private ValidatorInterface $validator;

    public function __construct(OrderService $orderService, ValidatorInterface $validator)
    {
        $this->orderService = $orderService;
        $this->validator = $validator;
    }

    /**
     * Siparişe ait tüm ürünleri listeleme
     * @Route ("/orders", name="orders", methods={"GET"})
     * @throws NonUniqueResultException
     */
    public function index(): JsonResponse
    {
        return RedirectHelper::success($this->orderService->index());
    }

    /**
     * Siparişe ürün ekleme veya mevcut ürünü güncelleme.
     * @Route ("/orders/product", name="order_store_product", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $attributes = GeneralHelper::getJson($request);
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
            return RedirectHelper::badRequest(GeneralHelper::getErrorMessages($errors));
        }

        $status = $this->orderService->storeProduct($attributes);
        switch ($status) {
            case OrderStoreStatus::SUCCESS:
                return RedirectHelper::store();
            case OrderStoreStatus::PRODUCT_STOCK:
                return RedirectHelper::badRequest(null, 'products.stock');
            default: //OrderStoreStatus::ERROR
                return RedirectHelper::badRequest();
        }
    }

    /**
     * Siparişten ürünü siler ve sipariş total fiyatını günceller.
     * @Route ("/orders/product/{productId}", name="order_remove_product", methods={"DELETE"})
     * @param $productId
     * @return JsonResponse
     */
    public function removeByProductId($productId): JsonResponse
    {
        if ($this->orderService->removeByProductId($productId)) {
            return RedirectHelper::destroy();
        }
        return RedirectHelper::badRequest();
    }

    /**
     * Sipariş bilgilerine göre indirimleri hesaplar
     * @Route ("/orders/discount", name="order_discount", methods={"GET"})
     * @return JsonResponse
     */
    public function discount(): JsonResponse
    {
        return RedirectHelper::success($this->orderService->discount());
    }
}
