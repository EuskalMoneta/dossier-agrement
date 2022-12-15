<?php

namespace App\Form;

use App\Entity\DossierAgrement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignatureElectroniqueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('iban',null, ['label' => 'IBAN'])
            ->add('bic',null, ['label' => 'BIC'])
            ->add('nomSignature',null, ['label' => 'Nom'])
            ->add('prenomSignature',null, ['label' => 'Prénom'])
            ->add('telephoneSignature',null, ['label' => 'Téléphone'])
            ->add('statutChargesDeveloppement',ChoiceType::class,
                [
                    'choices' => [
                        'en cours' => 'en cours',
                        'complet' => 'complet'
                    ],
                    'label' => "Statut du dossier"
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierAgrement::class,
        ]);
    }
}
