<?php

namespace App\Security;

use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->hasher = $hasher;
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiToken = $request->headers->get('Authorization');

        // If token is not passed in header
        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('Authentication token not found.');
        }

        // Skip beyond "Bearer "
        $apiToken = substr($apiToken, strlen('Bearer '));

        // Try to find token
        $tokenRepository = $this->em->getRepository(Token::class);
        $token = $tokenRepository->findOneBy(['accessToken' => $apiToken]);

        // If not found
        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Unknown authorization token.');
        }

        if ($token->isAccessTokenExpired()) {
            throw new CustomUserMessageAuthenticationException('This access token is expired.');
        }

        // Get user associated to the token
        $account = $token->getUser();

        return new SelfValidatingPassport(
            new UserBadge(
                $apiToken,
                fn () => $account
        ));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        $data = ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
