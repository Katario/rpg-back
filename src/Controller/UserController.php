<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegisterUserRequest;
use App\Factory\UserFactory;
use App\FormType\UserType;
use App\Repository\UserRepository;
use App\Validator\UniqueUserEmail;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/sign-in', name: 'sign_in', methods: ['GET', 'POST'])]
    public function signIn(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/sign_in.html.twig', [
            'controller_name' => 'UserController',
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/sign-up', name: 'sign_up', methods: ['GET', 'POST'])]
    public function signUp(
        Request $request,
        UserFactory $userFactory,
        UserRepository $userRepository,
    ): Response|RedirectResponse {
        $form = $this->formFactory->create(UserType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RegisterUserRequest $registration */
            $registration = $form->getData();

            try {
                $userRepository->save($userFactory->createOneFromRegistration($registration));

                return $this->redirectToRoute('sign_in');
            } catch (UniqueConstraintViolationException) {
                $form->get('email')->addError(new FormError(UniqueUserEmail::MESSAGE));
            }
        }

        // Passing the form (not its view) answers 422 when the submission is invalid.
        return $this->render('user/sign_up.html.twig', [
            'form' => $form,
        ]);
    }
}
