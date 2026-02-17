<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                    'label' => 'Nueva Contraseña',
                ],
                'second_options' => [
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                    'label' => 'Repetir Contraseña',
                ],
                'invalid_message' => 'Las contraseñas deben coincidir.',
                'mapped' => false,
                'constraints' => [
                    // CORREGIDO: Usamos sintaxis de argumentos (sin corchetes [])
                    new NotBlank(message: 'Por favor introduce una contraseña'),
                    new Length(
                        min: 6,
                        minMessage: 'Tu contraseña debe tener al menos {{ limit }} caracteres',
                        max: 4096
                    ),
                ],
            ])
        ;
    }
}