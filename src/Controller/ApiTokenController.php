<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
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
class ApiTokenController extends ApiAbstractController
{
    /**
     * Api resource.
     *
     * @var string $ressource
     */
    private string $resource = Token::class;

    #[Route('refresh-token/{refreshToken}/token', name: 'api_refresh_token', methods: ['POST'])]
    public function refreshToken(string $refreshToken, Request $request, EntityManagerInterface $em,
                                 ValidatorInterface $validator): Response
    {
        $token = $em->getRepository(Token::class)->findOneBy(['refreshToken' => $refreshToken]);

        if (!$token) {
            return $this->response(['message' => 'Unknown refresh token.'], 404);
        }

        if ($token->isRefreshTokenExpired()) {
            return $this->response(['message' => 'This refresh token is expired.'], 401);
        }

        $account = $token->getUser();
        $newToken = new Token();
        $account->setToken($newToken);

        $em->persist($account);
        $em->persist($newToken);
        $em->flush();

        return $this->response($token, 201);
    }
}