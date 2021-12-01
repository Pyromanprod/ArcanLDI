<?php

namespace App\Form;

use App\Entity\Order;
use Doctrine\DBAL\Types\BooleanType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IsCgvFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('is_cgv', CheckboxType::class,[
                'constraints' =>
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions générales de vente',
                    ])

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
