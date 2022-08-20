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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderController extends AbstractController
{
    private $orderService, $validator;

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
     * @param Request $request
     * @Route ("/orders", name="order_store", methods={"POST"})
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function store(Request $request): JsonResponse
    {
        $data = GeneralHelper::getJson($request);
        $errors = $this->validator->validate($data, new Assert\Collection([
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

        $status = $this->orderService->store($data);
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
