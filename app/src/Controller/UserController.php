<?php

namespace App\Controller;

use App\Helpers\Request;
use App\Services\TokenService;
use App\Resources\UserResource;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends RestController implements TokenAuthenticatedController
{
    #[Route("/api/users", name:"getuser", methods:["GET", "HEAD"])]
    public function show(EntityManagerInterface $em, SymfonyRequest $sr, UserRepository $ur, TokenService $ts)
    {
        $request = new Request($sr);
        $user = $request->getUser($ur, $ts);

        $userResource = new UserResource($em);

        return $this->handleResponse('Information successfully found', [
            'user' => $userResource->resource($user)
        ]);
    }

    #[Route("/api/users", name:"updateUser", methods:["POST"])]
    public function update(
        EntityManagerInterface $em,
        SymfonyRequest $sr,
        UserRepository $ur,
        UserPasswordHasherInterface $ph,
        TokenService $ts
    ) {
        $request = new Request($sr);
        $user = $request->getUser($ur, $ts);

        if (!isset($user)) {
            return $this->handleResponse('Wrong credentials', [], Response::HTTP_CONFLICT);
        }

        if (!$ph->isPasswordValid($user, $request->get('password'))) {
            return $this->handleResponse('Wrong credentials', [], Response::HTTP_UNAUTHORIZED);
        }

        $user->setLogin($request->get('login', $user->getLogin()));
        $user->setEmail($request->get('email', $user->getEmail()));
        $user->setFirstname($request->get('firstname', $user->getFirstname()));
        $user->setLastname($request->get('lastname', $user->getLastname()));

        $ur->persist();

        $userResource = new UserResource($em);

        return $this->handleResponse('Information successfully updated', [
            'user' => $userResource->resource($user)
        ]);
    }
}