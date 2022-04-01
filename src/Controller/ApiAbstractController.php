<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    /**
     * Request.
     *
     * @var Request $request
     */
    protected Request $request;

    /**
     * Entity manager.
     *
     * @var EntityManagerInterface $em
     */
    protected EntityManagerInterface $em;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $em, RequestStack $request)
    {
        $this->serializer = $serializer;
        $this->request = $request->getCurrentRequest();
        $this->em = $em;
    }

    /**
     * Return a response in json.
     *
     * @param object|array|null $data The data array
     * @param int $status The response status
     * @param array|null $groups Entity groups
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
            return throw new Exception('Request body is not a valid json.');
        }

        return $data;
    }

    /**
     * Remove the 'Bearer' prefix in 'Authorization' header.
     *
     * @param string $header The 'Authorization' header
     * @return string
     */
    protected function cleanHeaderToken(string $header): string
    {
        return substr($header, strlen('Bearer '));
    }

    /**
     * Get account via 'me' alias or its id.
     *
     * @param string|int $parameter The parameter
     * @return User
     * @throws EntityNotFoundException
     * @throws AccessDeniedException
     */
    protected function getAccountByParameter(string|int $parameter): User
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_USER');
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException($e->getMessage());
        }

        if ($parameter === 'me') {
            $token = $this->cleanHeaderToken($this->request->headers->get('Authorization'));
            $token = $this->em->getRepository(Token::class)->findOneBy(['accessToken' => $token])?:
                throw new EntityNotFoundException('Unknown authorization token.');
            $user = $token->getUser();
        } else {
            $user = $this->em->getRepository(User::class)->find($parameter) ?:
                throw new EntityNotFoundException('The resource you requested could not be found.');
        }

        $isAdmin = $this->isAuthenticatedAdmin();

        $authIdentifier = $this->getUser()->getUserIdentifier();
        $userIdentifier = $user->getUserIdentifier();

        if (!$isAdmin && $authIdentifier !== $userIdentifier) {
            throw new AccessDeniedException();
        }

        return $user;
    }

    /**
     * Is authenticated user admin.
     *
     * @return bool
     */
    protected function isAuthenticatedAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getUser()->getRoles());
    }

    /**
     * Update an entity from keys passed to the data array.
     *
     * @param object $entity The entity to update
     * @param array $data Associated array like "property" => "value"
     * @return object
     * @throws Exception
     */
    public function update(object $entity, array $data): object
    {
        foreach ($data as $key => $value) {
            $method = 'set'.ucfirst($key);
            if (method_exists($entity, $method)) {
                try {
                    $entity->$method($value);
                } catch (Exception) {
                    throw new Exception('Cannot update "' . $key . '" with "' . $value . '"');
                }
            } else {
                throw new Exception('Cannot update "' . $key . '", unknown property.');
            }
        }
        return $entity;
    }

    /**
     * Verify if the user didn't pass forbidden attributes to update.
     *
     * @param array $payload The payload array
     * @return bool
     * @throws Exception
     */
    protected function validateAccountPayload(array $payload): bool
    {
        $forbiddenValues = ['createdAt', 'updatedAt', 'token'];
        if (!$this->isAuthenticatedAdmin()) {
            $forbiddenValues[] = ['roles'];
        }

        foreach ($payload as $attribute => $value) {
            if (in_array($attribute, $forbiddenValues)) {
                throw new Exception('Updating '.$attribute.' is forbidden.');
            }
        }

        return true;
    }
}