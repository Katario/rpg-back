<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\RegisterUserRequest;
use App\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use App\Serializer\UserSerializer;
use App\Validator\UniqueUserEmail;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\AccessToken\HeaderAccessTokenExtractor;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserFactory $userFactory,
        UserRepository $userRepository,
        UserSerializer $userSerializer,
        RateLimiterFactoryInterface $registrationLimiter,
    ): JsonResponse {
        $limit = $registrationLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            return $this->json(
                ['error' => 'Too many registration attempts, please try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) max(0, $limit->getRetryAfter()->getTimestamp() - time())],
            );
        }

        $body = json_decode($request->getContent(), true);

        if (!is_array($body)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $registration = new RegisterUserRequest(
            email: is_string($body['email'] ?? null) ? trim($body['email']) : '',
            password: is_string($body['password'] ?? null) ? $body['password'] : '',
        );

        $violations = $validator->validate($registration);
        if (count($violations) > 0) {
            return $this->validationError($this->violationsByField($violations));
        }

        $user = $userFactory->createOneFromRegistration($registration);

        try {
            $userRepository->save($user);
        } catch (UniqueConstraintViolationException) {
            // Concurrent registration with the same email, caught by the unique index.
            return $this->validationError(['email' => [UniqueUserEmail::MESSAGE]]);
        }

        return $this->json($userSerializer->serialize($user), Response::HTTP_CREATED);
    }

    /**
     * Handled by the `json_login` authenticator of the `api` firewall (see security.yaml):
     * only reached when the request is not JSON.
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(
            ['error' => 'Send {"email", "password"} as JSON (Content-Type: application/json).'],
            Response::HTTP_BAD_REQUEST,
        );
    }

    /**
     * Revokes the access token used for this request (other sessions stay signed in).
     */
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function logout(Request $request, ApiTokenRepository $apiTokenRepository): Response
    {
        $plainToken = new HeaderAccessTokenExtractor()->extractAccessToken($request);
        $apiToken = $plainToken ? $apiTokenRepository->findOneByPlainToken($plainToken) : null;

        if ($apiToken) {
            $apiTokenRepository->delete($apiToken);
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, list<string>> $violations messages indexed by field name
     */
    private function validationError(array $violations): JsonResponse
    {
        return $this->json(
            ['error' => 'Validation failed', 'violations' => $violations],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function violationsByField(ConstraintViolationListInterface $violations): array
    {
        $byField = [];
        foreach ($violations as $violation) {
            $byField[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $byField;
    }
}
