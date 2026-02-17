<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class AvatarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('foto', FileType::class, [
                'label' => 'Sube tu foto de perfil (Máx 10KB)',
                'mapped' => false, 
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File(
                        maxSize: '10k', 
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                        ], 
                        mimeTypesMessage: 'Solo se permiten archivos JPG o PNG.',
                        maxSizeMessage: 'La imagen pesa demasiado. Máximo permitido: 10KB.'
                    )
                ],
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