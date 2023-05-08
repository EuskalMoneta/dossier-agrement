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
            ->add('denominationCommerciale', null, ['label' => "Raison sociale", 'required'=> true])
            /*->add('formeJuridique', ChoiceType::class, [
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
            ])*/
            ->add('emailPrincipal', EmailType::class, ['label' => 'Email'])
            ->add('nomDirigeant', null, ['label' => 'Nom'])
            ->add('civiliteDirigeant',ChoiceType::class,[
                'label' => 'Genre',
                'required' => false,
                'attr' => ['class' => ''],
                'choices'  => [
                    'Monsieur' => 'MR',
                    'Madame' => 'MME',
                    'Docteur' => 'DR',
                    'Maître' => 'MTRE',
                    'M.' => 'M'
                ]
            ])
            ->add('telephone', null, ['label' => 'Téléphone'])
            ->add('prenomDirigeant', null, ['label' => 'Prénom'])
            ->add('telephoneDirigeant', null, ['label' => 'Téléphone'])
            ->add('emailDirigeant', null, ['label' => 'Email'])
            ->add('fonctionDirigeant', null, ['label' => 'Fonction'])
            ->add('interlocuteurDirigeant', CheckboxType::class, ['required'=> false, 'label' => 'Est un interlocteur d\'Euskal Moneta'])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => 'Enregistrer'
            ])
            ->add('siteWeb')
            ->add('complementAdresse')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierAgrement::class,
        ]);
    }
}
