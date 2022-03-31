<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $response = new JsonResponse();

        if ($exception instanceof AccessDeniedException) {
            $response->setData(['message' => 'Full authentication is required to access this resource.']);
            $response->setStatusCode(401);
            $response->headers->add(['Content-Type' => 'application/json']);
        } else if ($exception instanceof HttpExceptionInterface) {
            $response->setData(['message' => 'Unknown API endpoint.']);
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->add(['Content-Type' => 'application/json']);
        } else {
            $response->setData(['message' => 'Internal server error.']);
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}