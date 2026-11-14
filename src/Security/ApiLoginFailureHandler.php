<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Failed POST /api/auth/login: 401, or 429 when login throttling kicks in.
 */
class ApiLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return new JsonResponse(
                ['error' => 'Too many failed login attempts, please try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
    }
}
