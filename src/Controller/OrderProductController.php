<?php

namespace App\Controller;

use App\FormRequest\OrderProductStoreRequest;
use App\FormRequest\OrderProductUpdateRequest;
use App\Helper\ResponseHelper;
use App\Service\OrderProductService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class OrderProductController extends AbstractController
{
    private OrderProductService $orderProductService;

    public function __construct(OrderProductService $orderProductService)
    {
        $this->orderProductService = $orderProductService;
    }

    /**
     * @Route ("/orders/product-order", name="store_order_product", methods={"POST"})
     * @param OrderProductStoreRequest $request
     * @return JsonResponse
     */
    public function store(OrderProductStoreRequest $request): JsonResponse
    {
        $this->orderProductService->storeOrderProduct($request->all());
        return ResponseHelper::store();
    }

    /**
     * @Route ("/orders/product-order/{orderProductId}", name="update_order_product", methods={"PUT"})
     * @param $orderProductId
     * @param OrderProductUpdateRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function update(OrderProductUpdateRequest $request, $orderProductId): JsonResponse
    {
        $this->orderProductService->updateOrderProduct($orderProductId, $request->all());
        return ResponseHelper::update();
    }

    /**
     * @Route ("/orders/product-order/{orderProductId}", name="destroy_order_product", methods={"DELETE"})
     * @param $orderProductId
     * @return JsonResponse
     */
    public function destroy($orderProductId): JsonResponse
    {
        $this->orderProductService->destroyOrderProduct($orderProductId);
        return ResponseHelper::destroy();
    }
}
