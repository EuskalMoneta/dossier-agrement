<?php

namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\Contact;
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

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            //récupération de l'adresse
            $dossierAgrement->setAdressePrincipale($request->get('adressePrincipale'));

            /******************************************************/
            /*****************    CONTACTS   **********************/
            /******************************************************/
            //on sauvegarde l'ancienne liste des contacts pour les suppressions
            $tabContacts = $dossierAgrement->getContacts()->toArray();

            //Gestion des contacts soumis par le formulaire
            if($request->get('dataContacts')){

                foreach ($request->get('dataContacts') as $contactJson){
                    $contactObjet = json_decode($contactJson);

                    if( substr($contactObjet->id, 0, 4) != 'TEMP'){
                        //on récupère le contact existant et on le retire du tableau
                        $contact = $em->getRepository(Contact::class)->find($contactObjet->id);
                        if (($key = array_search($contact, $tabContacts)) !== false) {
                            unset($tabContacts[$key]);
                        }
                    } else {
                        //création d'un nouveau contact
                        $contact = new Contact();
                    }

                    $contact->updateFormJsonObject($contactObjet);
                    $contact->setDossierAgrement($dossierAgrement);
                    $em->persist($contact);
                }
            }
            //On supprime les contacts qui ont été supprimés en front
            foreach ($tabContacts as $contact){
                $dossierAgrement->removeContact($contact);
                $em->remove($contact);
            }

            /******************************************************/
            /*****************    ADRESSES   **********************/
            /******************************************************/
            //on sauvegarde l'ancienne liste des adresses pour les suppressions
            $tabAdressesToDelete = $dossierAgrement->getAdresseActivites()->toArray();

            if($request->get('dataContacts')) {

                foreach ($request->get('dataAdresses') as $adresseJson) {
                    //on decode le json entier
                    $adresseObjet = json_decode($adresseJson);

                    if( substr($adresseObjet->id, 0, 4) != 'TEMP'){
                        //on récupère le contact existant et on le retire du tableau
                        $adresse = $em->getRepository(AdresseActivite::class)->find($adresseObjet->id);
                        if (($key = array_search($adresse, $tabAdressesToDelete)) !== false) {
                            unset($tabAdressesToDelete[$key]);
                        }
                    } else {
                        //création d'une nouvelle adresse
                        $adresse = new AdresseActivite();
                    }

                    $adresse->updateFormJsonObject($adresseObjet);
                    $adresse->setDossier($dossierAgrement);
                    $em->persist($adresse);
                }
            }

            //On supprime les contacts qui ont été supprimés en front
            foreach ($tabAdressesToDelete as $adresse){
                $dossierAgrement->removeAdresseActivite($adresse);
                $em->remove($adresse);
            }

            /******************************************************/
            /**************    ENREGISTREMENT   *******************/
            /******************************************************/
            $em->persist($dossierAgrement);
            $em->flush();

        }

        return $this->renderForm('dossier/coordonnees.html.twig', [
            'form' => $form,
            'dossierAgrement' => $dossierAgrement
        ]);

    }
}
