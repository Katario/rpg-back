<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Serializer\UserSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class MeController extends AbstractController
{
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function me(#[CurrentUser] User $user, UserSerializer $userSerializer): JsonResponse
    {
        return $this->json($userSerializer->serialize($user));
    }
}
