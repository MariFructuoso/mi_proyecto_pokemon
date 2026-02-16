<?php

namespace App\Form;

use App\Entity\Pokemon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PokemonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del Pokémon',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ej: Pikachu']
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo Elemental',
                'choices' => [
                    'Fuego 🔥' => 'Fuego',
                    'Agua 💧' => 'Agua',
                    'Planta 🌿' => 'Planta',
                    'Eléctrico ⚡' => 'Eléctrico',
                    'Psíquico 🔮' => 'Psíquico',
                    'Roca 🪨' => 'Roca',
                    'Normal ⭐' => 'Normal',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('imagen', FileType::class, [
                'label' => 'Imagen del Pokémon (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    // --- CORRECCIÓN AQUÍ ---
                    // En lugar de new File([ ... ]), usamos argumentos con nombre (sin corchetes de array)
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Por favor sube una imagen válida (JPG, PNG)'
                    )
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pokemon::class,
        ]);
    }
}