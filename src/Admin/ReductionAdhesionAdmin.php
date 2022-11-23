<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class ReductionAdhesionAdmin extends AbstractAdmin
{

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('nom', null, ['help' => "Apparaitra dans le dossier d'agrément. Ex : 'Bai Euskarari (-25 % de cotisation)' "])
            ->add('pourcentageReduction', null, ['help' => "Rentrer un entier. Ex: pour 25% de réduction, rentrer 25"])
            ->add('visible')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('nom')
            ->add('pourcentageReduction')
            ->add('visible')
            ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('nom')
            ->add('pourcentageReduction')
            ->add('visible')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }



    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('nom')
            ->add('pourcentageReduction')
            ->add('visible')
            ;
    }
}
