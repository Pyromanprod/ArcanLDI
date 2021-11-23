<?php

namespace App\Form;

use App\Entity\Picture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class AlbumPhotoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photos', FileType::class, [
                'multiple' => true,
                'mapped' => false,
                'constraints' => [
                    new Image([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                            ],
                            'mimeTypesMessage' => 'Fichier accépté : "jpeg, jpg, png, gif, webp"',
                            'maxSizeMessage' => 'Merci de ne pas dépasser {{ limit }} {{ suffixe }}',
                        ]
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Picture::class,
        ]);
    }
}
