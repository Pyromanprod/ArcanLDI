<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('price', MoneyType::class)
            ->add('game', EntityType::class, [
                'class' => 'App\Entity\Game',
                'choice_label' => 'name'
            ])
            ->add('stock', IntegerType::class)
            ->add('cgv', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File(
                        [
                            'mimeTypes' => 'application/pdf',
                            'mimeTypesMessage' => 'Le fichier doit etre en pdf',
                        ],
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
