<?php

namespace App\Controller;

use App\Helper\ResponseHelper;
use App\Service\OrderService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/orders")
 */
class OrderController extends AbstractController
{
    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Müşterinin siparişindeki tüm ürün bilgilerini döner.
     * @Route ("", name="orders", methods={"GET"})
     * @return JsonResponse
     * @throws Exception
     */
    public function index(): JsonResponse
    {
        return ResponseHelper::success($this->orderService->index());
    }

    /**
     * Siparisi kaydini tamamlar
     * @Route ("/complete", name="order_complete", methods={"GET"})
     * @return JsonResponse
     * @throws Exception
     */
    public function complete(): JsonResponse
    {
        $this->orderService->complete();
        return ResponseHelper::success();
    }
}
