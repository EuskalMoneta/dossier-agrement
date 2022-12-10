<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Show\ShowMapper;

final class UserAdmin extends AbstractAdmin
{

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('roles', CollectionType::class, [
                'allow_add' => true,
                "help" => "Pour donner les droits admin à un utilisateur : rajouter une nouvelle ligne avec la valeur 'ROLE_ADMIN'."
            ], [
                'edit' => 'inline',
                'inline' => 'table',
                'sortable' => 'position',
            ])

        ;
    }


    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('email')
            ->add('roles')
            ->add('nom')
            ->add('prenom')
            ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->addIdentifier('email')
            ->add('nom')
            ->add('prenom')
            ->add('roles')
        ;
    }



    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('email')
            ->add('roles')
            ->add('password')
            ->add('confirmationToken')
            ->add('nom')
            ->add('prenom')
            ;
    }
}
