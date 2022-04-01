<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } catch (AccessDeniedException $e) {
            return $this->response(['message' => $e->getMessage()], 403);
        }

        try {
            $account = $this->deserializeRequest($request, $this->resource);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 400);
        }

        $token = new Token();
        $account->setToken($token);

        $errors = $validator->validate($account);

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
                                  ValidatorInterface $validator): Response
    {
        try {
            $account = $this->getAccountByParameter($uid);
        } catch (AccessDeniedException $e) {
            return $this->response(['message' => $e->getMessage()], 401);
        } catch (EntityNotFoundException $e) {
            return $this->response(['message' => $e->getMessage()], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->response(['message' => 'Data is empty or wrongly formatted in json.'], 400);
        }

        try {
            $this->validateAccountPayload($data);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 403);
        }

        try {
            $account = $this->update($account, $data);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 422);
        }

        $account->setUpdatedAt(new DateTime());

        $errors = $validator->validate($account);

        if (count($errors)) {
            return $this->response($errors, 422);
        }

        $em->persist($account);
        $em->flush();

        return $this->response($account, 200);
    }
}