<?php

namespace App\Controller;

use App\Helper\ResponseHelper;
use App\Service\CartService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/home")
 */
class HomeController extends AbstractController
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("")
     * @return void
     */
    public function index(Request $request)
    {
        $request->setLocale('tr');
        //dd($this->translator->trans('test translation'));

        dd($this->translator->trans('a.b.c'));
    }
}
