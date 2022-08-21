<?php

namespace App\EventSubscriber;

use App\Helper\RedirectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if($event->getThrowable() instanceof HttpException){
            $event->setResponse(RedirectHelper::badRequest([], $event->getThrowable()->getMessage()));
        }

        $event->setResponse(RedirectHelper::internalServerError([
            'code' => $event->getThrowable()->getCode(),
            'file' => $event->getThrowable()->getFile(),
            'line' => $event->getThrowable()->getLine(),
        ],
            $event->getThrowable()->getMessage()
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }
}
