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
                    'De base' => 'minimale'
                ]
            ])
            ->add('fraisDeDossier',null, ['label' => 'Frais de dossier', 'required' => true, 'attr' => ['class' => 'fdd']])
            ->add('fraisDeDossierRecu',ChoiceType::class,[
                'label' => 'Frais de dossier reçu par',
                'attr' => ['class' => 'typeCotisation'],
                'choices'  => [
                    '' => '',
                    'chèque' => 'chèque',
                    'espèces' => 'espèces',
                    'virement' => 'virement',
                    'offert' => 'offert'
                ]
            ])

            ->add('compteNumeriqueBool',null, ['label' => 'Compte numérique - si oui, email'])
            ->add('compteNumerique',null, ['label' => ' '])
            ->add('terminalPaiementBool',null, ['label' => 'Terminal de paiement - si oui, combien'])
            ->add('terminalPaiement',null, ['label' => ' '])
            ->add('euskokartSurAppPro',null, ['label' => 'Paiement par euskokart sur l’application du PRO'])
            ->add('euskopayBool',null, ['label' => 'Euskopay - si oui combien de QR codes'])
            ->add('euskopay',null, ['label' => ' '])
            ->add('paiementViaEuskopay',null, ['label' => 'Paiement possible par le PRO depuis l’application euskopay'])
            ->add('siren',null, ['label' => 'SIREN'])
            ->add('recevoirNewsletter')
            ->add('autocollantVitrine')
            ->add('autocollantPanneau')
            ->add('typeAutocollant', ChoiceType::class,[
                'label' => 'Type autocollant',
                'choices'  => [
                    'Bilingue/euskaraz' => 'Bilingue/euskaraz',
                    'Premiers mots en langue basque/lehen hitza euskaraz' => 'Premiers mots en langue basque/lehen hitza euskaraz'
                ]
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
