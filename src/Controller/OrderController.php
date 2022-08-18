<?php

namespace App\Controller;

use App\Helper\RedirectHelper;
use App\Service\OrderService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
    private $orderService;
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * @Route("/orders", name="app_order", methods={"GET"})
     */
    public function index(ManagerRegistry $doctrine): Response
    {
        return RedirectHelper::success(
            $this->orderService->index()
        );

        /*
        $entityManager = $doctrine->getManager();

        $customer = new Customer();
        $customer->setName('Test');
        $customer->setSince(new \DateTime('@'.strtotime('now')));
        $customer->setRevenue(3432333.32);
        $entityManager->persist($customer);
        $entityManager->flush();

        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);*/
    }
}
