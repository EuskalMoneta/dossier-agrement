<?php

namespace App\Form;

use App\Entity\DossierAgrement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoordonneesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('denominationCommerciale')
            ->add('formeJuridique', ChoiceType::class, [
                'choices'  => [
                    'Micro' => 'Micro',
                    'EIRL/EURL' => 'EIRL/EURL',
                    'SARL' => 'SARL',
                    'SA' => 'SA',
                    'SNC' => 'SNC',
                    'COOP' => 'COOP',
                    'GAEC' => 'GAEC',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('emailPrincipal', EmailType::class, ['label' => 'Email'])
            ->add('nomDirigeant', null, ['label' => 'Nom'])
            ->add('prenomDirigeant', null, ['label' => 'Prénom'])
            ->add('telephoneDirigeant', null, ['label' => 'Téléphone'])
            ->add('emailDirigeant', null, ['label' => 'Email'])
            ->add('fonctionDirigeant', null, ['label' => 'Fonction'])
            ->add('interlocuteurDirigeant', CheckboxType::class, ['label' => 'Est un interlocteur d\'euskal moneta'])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => 'Enregistrer'
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierAgrement::class,
        ]);
    }
}
