<?php

namespace App\Controller;

use App\FormRequest\OrderProductStoreRequest;
use App\Helper\ResponseHelper;
use App\Service\OrderProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * * @Route("/orders/order-product")
 */
class OrderProductController extends AbstractController
{
    private OrderProductService $orderProductService;

    public function __construct(OrderProductService $orderProductService)
    {
        $this->orderProductService = $orderProductService;
    }

    /**
     * Siparişe ürünü ekler
     * @Route ("", name="store_order_product", methods={"POST"})
     * @param OrderProductStoreRequest $request
     * @return JsonResponse
     */
    public function store(OrderProductStoreRequest $request): JsonResponse
    {
        $this->orderProductService->storeOrderProduct($request->all());
        return ResponseHelper::store();
    }
}
