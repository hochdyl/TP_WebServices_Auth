<?php

namespace App\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class ApiAbstractController extends AbstractController
{
    /**
     * Data serializer.
     *
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Return a response in json.
     *
     * @param object|array|null $data The data array.
     * @param int $status The response status.
     * @param array|null $groups Entity groups.
     * @return Response
     */
    protected function response(object|array|null $data, int $status, array $groups = null): Response
    {
        if (!$groups) {
            $groups[] = 'public';
        }

        $data = $this->serializer->serialize($data, 'json', ['groups' => $groups]);
        return new Response($data, $status, ['Content-Type' => 'application/json']);
    }

    /**
     * Handle a request content.
     *
     * @throws Exception
     */
    protected function deserializeRequest($request, string $resource)
    {
        try {
            $data = $this->serializer->deserialize($request->getContent(), $resource, 'json');
        } catch (NotEncodableValueException) {
            return throw new Exception('Data is wrongly formatted in json.');
        }

        return $data;
    }
}