<?php

namespace App\Form;

use App\Entity\RoleGroupe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleAddFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',EntityType::class,[
                'class' => 'App\Entity\RoleGroupe',
                'choice_label' => 'name',
                'multiple' => 'true',
                'expanded' => 'true'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RoleGroupe::class,
        ]);
    }
}
