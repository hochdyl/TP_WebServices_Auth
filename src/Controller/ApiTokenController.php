<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/')]
class ApiTokenController extends ApiAbstractController
{
    /**
     * Api resource.
     *
     * @var string $ressource
     */
    private string $resource = Token::class;

    #[Route('refresh-token/{refreshToken}/token', name: 'api_refresh_token', methods: ['POST'])]
    public function refreshToken(string $refreshToken, EntityManagerInterface $em): Response
    {
        // Find token by 'refreshToken' value.
        $token = $em->getRepository(Token::class)->findOneBy(['refreshToken' => $refreshToken]);

        // If token is not found.
        if (!$token) {
            return $this->response(['message' => 'Unknown refresh token.'], 404);
        }

        // If token is expired.
        if ($token->isRefreshTokenExpired()) {
            return $this->response(['message' => 'This refresh token is expired.'], 401);
        }

        // Create and replace user token.
        $account = $token->getUser();
        $newToken = new Token();
        $account->setToken($newToken);

        $em->persist($account);
        $em->persist($newToken);
        $em->flush();

        return $this->response($newToken, 201);
    }

    #[Route('token', name: 'api_access_token', methods: ['POST'])]
    public function accessToken(Request $request, EntityManagerInterface $em,
                                UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get request content.
        $data = json_decode($request->getContent(), true);

        // If content is empty or wrongly formatted.
        if (!$data) {
            return $this->response(['message' => 'Request is empty or wrongly formatted in json.'], 400);
        }

        // If content does not contain login and password.
        if (!array_key_exists('login', $data) || !array_key_exists('password', $data)) {
            return $this->response(['message' => 'Request is missing login and/or password.'], 422);
        }

        // Find account by 'login' value.
        $account = $em->getRepository(User::class)->findOneBy(['login' => $data['login']]);

        // If account is not found.
        if (!$account) {
            return $this->response(['message' => 'Authentication failed.'], 401);
        }

        // If password does not match.
        if (!$passwordHasher->isPasswordValid($account, $data['password'])) {
            return $this->response(['message' => 'Authentication failed.'], 401);
        }

        // Create and replace user token.
        $newToken = new Token();
        $account->setToken($newToken);

        $em->persist($account);
        $em->persist($newToken);
        $em->flush();

        return $this->response($newToken, 201);
    }

    #[Route('validate/{accessToken}', name: 'api_validate_access_token', methods: ['GET'])]
    public function validateAccessToken(string $accessToken, EntityManagerInterface $em): Response
    {
        // Find token by 'accessToken' value.
        $token = $em->getRepository(Token::class)->findOneBy(['accessToken' => $accessToken]);

        // If token is not found.
        if (!$token) {
            return $this->response(['message' => 'Unknown access token.'], 404);
        }

        // If token is expired.
        if ($token->isAccessTokenExpired()) {
            return $this->response(['message' => 'This access token is expired.'], 401);
        }

        return $this->response($token, 201, ['accessToken']);
    }
}