<?php

namespace App\Form;

use App\Entity\DossierAgrement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class SignatureElectroniqueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('iban',null, ['label' => 'IBAN'])
            ->add('bic',null, ['label' => 'BIC'])
            ->add('nomSignature',null, ['label' => 'Nom'])
            ->add('prenomSignature',null, ['label' => 'Prénom'])
            //->add('telephoneSignature',null, ['label' => 'Téléphone'])
            ->add('telephoneSignature', TelType::class,[
                'required' => false,
                'label' => 'Téléphone',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\+[0-9]{2} [0-9] [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}$/',
                        'message' => 'Le numéro doit commencer par +33 ou un autre préfixe',
                    ])
                ],
            ])
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
