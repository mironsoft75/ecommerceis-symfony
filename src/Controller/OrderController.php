<?php

namespace App\Controller;

use App\Helper\ResponseHelper;
use App\Service\OrderService;
use Doctrine\ORM\NonUniqueResultException;
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
     * @Route ("", name="orders", methods={"GET"})
     * @return JsonResponse
     * @throws Exception
     */
    public function index(): JsonResponse
    {
        return ResponseHelper::success($this->orderService->index());
    }

    /**
     * @Route ("/discount", name="order_discount", methods={"GET"})
     * @return JsonResponse
     */
    public function discount(): JsonResponse
    {
        return ResponseHelper::success($this->orderService->discount());
    }
}
