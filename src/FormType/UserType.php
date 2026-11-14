<?php

declare(strict_types=1);

namespace App\FormType;

use App\Dto\RegisterUserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Web sign-up form: same rules as the API registration (constraints of RegisterUserRequest).
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterUserRequest::class,
            // The DTO is immutable: build it from the submitted values.
            'empty_data' => static fn (FormInterface $form): RegisterUserRequest => new RegisterUserRequest(
                email: (string) $form->get('email')->getData(),
                password: (string) $form->get('password')->getData(),
            ),
        ]);
    }
}
