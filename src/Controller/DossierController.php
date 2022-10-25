<?php

namespace App\Controller;

use App\Entity\DossierAgrement;
use App\Form\CoordonneesFormType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{

    #[Route('/dossier/nouveau', name: 'app_dossier_nouveau')]
    public function nouveauDossier(Request $request, EntityManagerInterface $em): Response
    {
        if($request->isMethod('post')){
            $dossierAgrement = new DossierAgrement();

            $dossierAgrement->setLibelle($request->get('nom-dossier'));
            $dossierAgrement->setType($request->get('type-dossier'));

            $em->persist($dossierAgrement);
            $em->flush();

            return $this->redirectToRoute('app_dossier_coordonnees', ['id' => $dossierAgrement->getId()]);
        }

        return $this->render('dossier/nouveau.html.twig', []);
    }


    #[Route('/dossier/coordonnees/{id}', name: 'app_dossier_coordonnees')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function coordonnees(DossierAgrement $dossierAgrement, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CoordonneesFormType::class, $dossierAgrement);

        if($request->isMethod('post')){

        }

        return $this->renderForm('dossier/coordonnees.html.twig', [
            'form' => $form,
            'dossierAgrement' => $dossierAgrement
        ]);

    }
}
