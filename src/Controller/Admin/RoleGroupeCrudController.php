<?php

namespace App\Controller\Admin;

use App\Entity\RoleGroupe;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RoleGroupeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RoleGroupe::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [

            TextField::new('name'),
            AssociationField::new('game','game')

        ];
    }

}
