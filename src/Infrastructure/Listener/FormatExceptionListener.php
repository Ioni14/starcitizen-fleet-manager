<?php

namespace App\Infrastructure\Listener;

use App\Domain\Exception\DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

class FormatExceptionListener
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        if (!$e instanceof DomainException) {
            return;
        }

        $response = new JsonResponse($this->serializer->serialize([
            'error' => $e->error,
            'errorMessage' => $e->userMessage,
            'context' => $e->context,
        ], 'json'), $e::$notFound ? 404 : 400, [], true);

        $event->setResponse($response);
    }
}
