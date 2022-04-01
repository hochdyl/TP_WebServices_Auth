<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use App\Service\EntityUpdaterService;
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
            return $this->response(['message' => $e->getMessage()], 401);
        }

        try {
            $account = $this->deserializeRequest($request, $this->resource);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 400);
        }

        // Validate entity.
        $errors = $validator->validate($account);

        if (count($errors)) {
            return $this->response($errors, 422);
        }

        $token = new Token($account);
        $em->persist($account);
        $em->persist($token);
        $em->flush();

        return $this->response($account, 201);
    }

    #[Route('account/{uid}', name: 'api_get_account', methods: ['GET'])]
    public function getAccount(int|string $uid, Request $request, UserRepository $userRepository): Response
    {
        // TODO : L'UID "me" est un alias représentant l'utilisateur à qui appartient l'access token.
        // TODO : Un utilisateur anonyme ne peut récupérer aucun compte.
        // TODO : Un utilisateur connecté n'ayant pas le rôle ROLE_ADMIN ne peut récupérer que son compte (via son UID ou l'alias "me").
        // TODO : Un utilisateur connecté ayant le rôle ROLE_ADMIN peut récupérer n'importe quel compte.

        // Differencis un id et un "me" sur les parametres de la route
        $test = $request->attributes->get('_route_params');
dd($test);

        try {
            $this->denyAccessUnlessGranted('ROLE_USER');
        } catch (AccessDeniedException $e) {
            return $this->response(['message' => $e->getMessage()], 401);
        }

        $account = $userRepository->find($uid);

        if(!$account) {
            return $this->response(['message' => 'The resource you requested could not be found.'], 404);
        }

        return $this->response($account, 200);
    }

    #[Route('account/{uid}', name: 'api_update_account', methods: ['PUT'])]
    public function updateAccount(int|string $uid, Request $request, UserRepository $userRepository,
                                EntityManagerInterface $em, EntityUpdaterService $updater,
                                ValidatorInterface $validator): Response
    {
        // TODO : Permet l'édition d'un compte utilisateur.
        // TODO : Seul un "ROLE_ADMIN" peut éditer les roles.
        // TODO : Un "ROLE_ADMIN" peut promouvoir un "ROLE_USER" en "ROLE_ADMIN"
        // TODO : Un compte ne disposant pas de "ROLE_ADMIN" ne peut éditer que son compte via sont UID ou l'alias "me"

        $account = $userRepository->find($uid);

        if (!$account) {
            return $this->response(['message' => 'The resource you requested could not be found.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->response(['message' => 'Data is empty or wrongly formatted in json.'], 400);
        }

        // Update entity from request data.
        try {
            $account = $updater->update($account, $data);
        } catch (Exception $e) {
            return $this->response(['message' => $e->getMessage()], 422);
        }

        // Validate entity.
        $errors = $validator->validate($account);

        if (count($errors)) {
            return $this->response($errors, 422);
        }

        $em->persist($account);
        $em->flush();

        return $this->response($account, 200);
    }

    #[Route('account/login', name: 'api_login_account', methods: ['GET'])]
    public function login(Request $request): Response
    {
        // TODO : Verifier les identifiants
        // TODO : Renvoyer un bearer token si c'est bon

        return $this->response(['key' => 'value'], 200);
    }
}