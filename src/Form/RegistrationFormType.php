<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
// Importamos la librería del Captcha
use Gregwar\CaptchaBundle\Type\CaptchaType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre de Entrenador',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Por favor, introduce un nombre.'),
                ],
            ])
            ->add('email', null, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Por favor, introduce una contraseña'),
                    new Length(
                        min: 6,
                        minMessage: 'Tu contraseña debe tener al menos {{ limit }} caracteres',
                        max: 4096
                    ),
                ],
            ])
            // AQUÍ ESTÁ EL CAPTCHA NUMÉRICO
            ->add('captcha', CaptchaType::class, [
                'label' => 'Código de seguridad (escribe los números)',
                'attr' => ['class' => 'form-control'],
                'invalid_message' => 'El código de seguridad no es correcto.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}