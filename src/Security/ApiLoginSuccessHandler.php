<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Serializer\UserSerializer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Successful POST /api/auth/login: issues an access token and returns it with the user.
 */
class ApiLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly ApiTokenManager $apiTokenManager,
        private readonly UserSerializer $userSerializer,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            throw new \LogicException(sprintf('Expected an instance of "%s".', User::class));
        }

        $now = new \DateTimeImmutable();

        return new JsonResponse([
            'token' => $this->apiTokenManager->issue($user, $now),
            'expiresAt' => $now->modify(ApiTokenManager::LIFETIME)->format(\DATE_ATOM),
            'user' => $this->userSerializer->serialize($user),
        ]);
    }
}
