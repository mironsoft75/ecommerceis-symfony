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
     * @Route ("", name="store_order_product", methods={"POST"})
     * @param OrderProductStoreRequest $request
     * @return JsonResponse
     */
    public function store(OrderProductStoreRequest $request): JsonResponse
    {
        $this->orderProductService->storeOrderProduct($request->all());
        return ResponseHelper::store();
    }

    /**
     * @Route ("/{orderProductId}", name="update_order_product", methods={"PUT"})
     * @param int $orderProductId
     * @param OrderProductUpdateRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function update(OrderProductUpdateRequest $request, int $orderProductId): JsonResponse
    {
        $this->orderProductService->updateOrderProduct($request->all(), $orderProductId);
        return ResponseHelper::update();
    }

    /**
     * @Route ("/{orderProductId}", name="destroy_order_product", methods={"DELETE"})
     * @param $orderProductId
     * @return JsonResponse
     */
    public function destroy($orderProductId): JsonResponse
    {
        $this->orderProductService->destroyOrderProduct($orderProductId);
        return ResponseHelper::destroy();
    }
}
