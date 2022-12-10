<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\DatePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class DossierAgrementAdmin extends AbstractAdmin
{

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Content', ['class' => 'col-md-8'])
            ->add('created', DatePickerType::class, [
                'required' => false,
                'label' => "Date de création",
                'dp_language'=>'fr',
            ])
            ->add('libelle')
            ->add('statutChargesDeveloppement', ChoiceType::class,
                [
                    'choices' => [
                        'nouveau' => 'nouveau',
                        'en cours' => 'en cours',
                        'valide' => 'valide'
                    ],
                    'required' => false,
                    'label' => "Statut du dossier"
                ]
            )
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

            ->add('etat', ChoiceType::class,
                [
                    'choices' => [
                        'nouveau' => 'nouveau',
                        'en cours' => 'en cours',
                        'valide' => 'valide',
                        'rejeté' => 'rejeté'
                    ],
                    'required' => false,
                    'label' => "Statut agrément"
                ]
            )
            ->add('dateAgrement', DatePickerType::class, [
                'required' => false,
                'label' => "Date d'Agrément",
                'dp_language'=>'fr',
            ])
            ->end()
            ->with('Contact', ['class' => 'col-md-4'])
            ->add('emailPrincipal')
            ->add('nomDirigeant')
            ->add('prenomDirigeant')
            ->add('utilisateur', null, ['label' => "Chargé de dev"])
            ->end()
            ->with('contact')
            ->add('idExterne')
            ->end()


        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('libelle')
            ->add('type')
            ->add('created', DateRangeFilter::class, ['label' => 'Date de création dossier'])
            ->add('dateAgrement', DateRangeFilter::class, ['label' => "Date d'Agrément"])
            ->add('denominationCommerciale')
            ->add('formeJuridique')
            ->add('emailPrincipal')
            ->add('nomDirigeant')
            ->add('prenomDirigeant')
            ->add('utilisateur', null, ['label' => 'Chargé de développement'])
            ->add('statutChargesDeveloppement',   ChoiceFilter::class, ['label' => 'Statut chargé de dev',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => [
                            'nouveau' => 'nouveau',
                            'en cours' => 'en cours',
                            'valide' => 'valide'],
                        'required' => false

                    ]
                ]
            )
            ->add('etat',   ChoiceFilter::class, ['label' => 'Statut agrément',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => [
                            'nouveau' => 'nouveau',
                            'en cours' => 'en cours',
                            'valide' => 'valide',
                            'rejeté' => 'rejeté'],
                        'required' => false

                    ]
                ]
            )
            ->add('montant')
            ->add('fraisDeDossier')
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
            ->add('utilisateur', null, ['label' => 'Chargé de développement'])
            ->add('dateAgrement')
            ->add('montant')
            ->add('fraisDeDossier')
            ->add('statutChargesDeveloppement', null, ['label' => "Statut chargé de dev"])
            ->add('etat', null, ['label' => "Statut agrément"])
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

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        // display the first page (default = 1)
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::PER_PAGE] = 100;
        // reverse order (default = 'ASC')
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        // name of the ordered field (default = the model's id field, if any)
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }
}
