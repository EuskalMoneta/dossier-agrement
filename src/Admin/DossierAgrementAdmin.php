<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\Form\Type\DatePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class DossierAgrementAdmin extends AbstractAdmin
{

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('created', DatePickerType::class, [
                'required' => false,
                'label' => "Date de création",
                'dp_language'=>'fr',
            ])
            ->add('libelle')
            ->add('type', ChoiceType::class,
                [
                    'choices' => [
                        'professionnel' => 'professionnel',
                        'association' => 'association'
                    ],
                    'required' => false,
                    'label' => "Type de dossier"
                ]
            )
            ->add('emailPrincipal')
            ->add('etat', ChoiceType::class,
                [
                    'choices' => [
                        'nouvelle' => 'nouvelle',
                        'encours' => 'encours',
                        'valide' => 'valide'
                    ],
                    'required' => false
                ]
            )
            ->add('dateAgrement', DatePickerType::class, [
                'required' => false,
                'label' => "Date d'Agrément",
                'dp_language'=>'fr',
            ])
            ->add('codePrestataire')
            ->add('idExterne')

        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('libelle')
            ->add('type')
            ->add('denominationCommerciale')
            ->add('formeJuridique')
            ->add('emailPrincipal')
            ->add('nomDirigeant')
            ->add('prenomDirigeant')
        ;
    }

    protected function configureBatchActions(array $actions): array
    {
        if (
            $this->hasRoute('edit') && $this->hasAccess('edit') &&
            $this->hasRoute('delete') && $this->hasAccess('delete')
        ) {
            $actions['generation'] = [
                'label' => 'Générer un compte rendu',
                'ask_confirmation' => false,
                'controller' => 'App\Controller\GestionController::batchGenerationAction'
            ];
        }

        return $actions;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->addIdentifier('libelle')
            ->add('type')
            ->add('denominationCommerciale')
            ->add('emailPrincipal')
            ->add('nomDirigeant')
            ->add('prenomDirigeant')
        ;
    }



    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('libelle')
            ->add('type')
            ->add('denominationCommerciale')
            ->add('formeJuridique')
            ->add('adressePrincipale')
            ->add('emailPrincipal')
            ->add('nomDirigeant')
            ->add('prenomDirigeant')
            ->add('telephoneDirigeant')
            ->add('emailDirigeant')
            ->add('fonctionDirigeant')
            ->add('interlocuteurDirigeant')
        ;
    }
}
