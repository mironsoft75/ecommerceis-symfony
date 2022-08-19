<?php

namespace App\Controller;

use App\Helper\RedirectHelper;
use App\Service\OrderService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
     * @Route ("/orders", name="app_order", methods={"GET"})
     * @throws NonUniqueResultException
     */
    public function index(): Response
    {
        return RedirectHelper::success($this->orderService->index());
    }
}
