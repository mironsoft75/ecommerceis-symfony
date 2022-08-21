<?php

namespace App\Controller;

use App\Enums\OrderStoreStatus;
use App\Helper\GeneralHelper;
use App\Helper\RedirectHelper;
use App\Service\OrderService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
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
     * @Route ("/orders", name="order_store", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function store(Request $request): JsonResponse
    {
        $attributes = GeneralHelper::getJson($request);
        $status = $this->orderService->store($attributes);
        if (is_array($status)) {
            return RedirectHelper::badRequest($status);
        } else {
            switch ($status) {
                case OrderStoreStatus::SUCCESS:
                    return RedirectHelper::store();
                case OrderStoreStatus::PRODUCT_STOCK:
                    return RedirectHelper::badRequest(null, 'products.stock');
                default: //OrderStoreStatus::ERROR
                    return RedirectHelper::badRequest();
            }
        }
    }

    /**
     * @Route ("/orders/product/{productId}", name="order_remove_product", methods={"DELETE"})
     * @param $productId
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function removeByProductId($productId): JsonResponse
    {
        if ($this->orderService->removeByProductId($productId)) {
            return RedirectHelper::destroy();
        } else {
            return RedirectHelper::badRequest();
        }
    }
}
