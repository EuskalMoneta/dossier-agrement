<?php

namespace App\Form;

use App\Entity\DossierAgrement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OptionsTechniqueProFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nbSalarie',null, ['label' => 'Nombre de salariés', 'attr' => ['class' => 'nbSalarie']])
            ->add('montant',null, ['label' => 'Montant', 'attr' => ['class' => 'montant']])
            ->add('typeCotisation',ChoiceType::class,[
                'label' => 'Type de cotisation',
                'attr' => ['class' => 'typeCotisation'],
                'choices'  => [
                    'Solidaire' => 'solidaire',
                    'Minimale' => 'minimale'
                ]
            ])
            ->add('fraisDeDossier',null, ['label' => 'Frais de dossier', 'attr' => ['class' => 'fdd']])
            ->add('fraisDeDossierRecu',ChoiceType::class,[
                'label' => 'Frais de dossier reçu par',
                'attr' => ['class' => 'typeCotisation'],
                'choices'  => [
                    '' => '',
                    'chèque' => 'chèque',
                    'espèces' => 'espèces',
                    'virement' => 'virement'
                ]
            ])

            ->add('compteNumeriqueBool',null, ['label' => 'Compte numérique - si oui, email'])
            ->add('compteNumerique',null, ['label' => ' '])
            ->add('terminalPaiementBool',null, ['label' => 'Terminal de paiement - si oui, combien'])
            ->add('terminalPaiement',null, ['label' => ' '])
            ->add('euskopayBool',null, ['label' => 'Euskopay - si oui, combien'])
            ->add('euskopay',null, ['label' => ' '])
            ->add('paiementViaEuskopay',null, ['label' => 'Paiement possible sur l’application euskopay'])
            ->add('siren',null, ['label' => 'SIREN'])
            ->add('recevoirNewsletter')
            ->add('autocollantVitrine')
            ->add('autocollantPanneau')
            ->add('typeAutocollant', ChoiceType::class,[
                'label' => 'Type autocollant',
                'choices'  => [
                    'Bilingue/euskaraz' => 'Bilingue/euskaraz',
                    'Premiers mots en langue basque/lehen hitza euskara' => 'Premiers mots en langue basque/lehen hitza euskara'
                ]
            ])
            ->add('note')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierAgrement::class,
        ]);
    }
}
