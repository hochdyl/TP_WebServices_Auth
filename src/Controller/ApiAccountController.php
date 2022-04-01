<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api/')]
class ApiAccountController extends ApiAbstractController
{
    /**
     * Api resource.
     *
     * @var string $ressource
     */
    private string $resource = User::class;

    #[Route('account', name: 'api_add_account', methods: ['POST'])]
    public function addAccount(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        // If authenticated user is not admin.
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } catch (AccessDeniedException $e) {
            return $this->response(['message' => $e->getMessage()], 403);
        }

        // If request content is wrongly formatted.
        try {
            $account = $this->deserializeRequest($request, $this->resource);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 400);
        }

        // Create and replace user token.
        $token = new Token();
        $account->setToken($token);

        // Validate the account.
        $errors = $validator->validate($account);

        // If there are errors in validation.
        if (count($errors)) {
            return $this->response($errors, 422);
        }

        $em->persist($account);
        $em->persist($token);
        $em->flush();

        return $this->response($account, 201);
    }

    #[Route('account/{uid}', name: 'api_get_account', methods: ['GET'])]
    public function getAccount(int|string $uid): Response
    {
        // Find account with id parameter or 'me' alias.
        try {
            $account = $this->getAccountByParameter($uid);
        } catch (AccessDeniedException $e) {
            return $this->response(['message' => $e->getMessage()], 401);
        } catch (EntityNotFoundException $e) {
            return $this->response(['message' => $e->getMessage()], 404);
        }

        return $this->response($account, 200);
    }

    #[Route('account/{uid}', name: 'api_update_account', methods: ['PUT'])]
    public function updateAccount(int|string $uid, Request $request, EntityManagerInterface $em,
                                  ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Find account with id parameter or 'me' alias.
        try {
            $account = $this->getAccountByParameter($uid);
        } catch (AccessDeniedException $e) {
            return $this->response(['message' => $e->getMessage()], 401);
        } catch (EntityNotFoundException $e) {
            return $this->response(['message' => $e->getMessage()], 404);
        }

        // Get request content.
        $data = json_decode($request->getContent(), true);

        // If content is empty or wrongly formatted.
        if (!$data) {
            return $this->response(['message' => 'Request is empty or wrongly formatted in json.'], 400);
        }

        // If request content contain password.
        if (array_key_exists('password', $data)) {
            $data['password'] = $passwordHasher->hashPassword($account, $data['password']);
        }

        // Verify if request content don't update forbidden attributes.
        try {
            $this->validateAccountPayload($data);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 403);
        }

        // Update account.
        try {
            $account = $this->update($account, $data);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 422);
        }

        // Change 'updatedAt' datetime.
        $account->setUpdatedAt(new DateTime());

        // Validate the account.
        $errors = $validator->validate($account);

        // If there are errors in validation.
        if (count($errors)) {
            return $this->response($errors, 422);
        }

        $em->persist($account);
        $em->flush();

        return $this->response($account, 200);
    }
}