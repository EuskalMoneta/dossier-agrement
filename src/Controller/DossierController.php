<?php

namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\CategorieAnnuaire;
use App\Entity\Contact;
use App\Entity\Defi;
use App\Entity\DossierAgrement;
use App\Entity\Fournisseur;
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

        $categoriesAnnuaire = $em->getRepository(CategorieAnnuaire::class)->findBy(['type' => 'eusko']);

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

                    if(!str_starts_with($contactObjet->id,'TEMP')){
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

            if($request->get('dataAdresses')) {

                foreach ($request->get('dataAdresses') as $adresseJson) {
                    //on decode le json entier
                    $adresseObjet = json_decode($adresseJson);

                    if( !str_starts_with($adresseObjet->id, 'TEMP')){
                        //on récupère le contact existant et on le retire du tableau
                        $adresse = $em->getRepository(AdresseActivite::class)->find($adresseObjet->id);
                        if (($key = array_search($adresse, $tabAdressesToDelete)) !== false) {
                            unset($tabAdressesToDelete[$key]);
                        }
                    } else {
                        //création d'une nouvelle adresse
                        $adresse = new AdresseActivite();
                    }

                    //gestion des categories de l'annuaire
                    $adresse->cleanCategoriesAnnuaire();
                    foreach ($adresseObjet->categoriesAnnuaire as $categorieId){
                        $categorie = $em->getRepository(CategorieAnnuaire::class)->find($categorieId);
                        if($categorie){
                            $adresse->addCategoriesAnnuaire($categorie);
                        }
                    }

                    //enregistrement
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

            return $this->redirectToRoute('app_dossier_defis', ['id' => $dossierAgrement->getId()]);
        }

        return $this->renderForm('dossier/coordonnees.html.twig', [
            'form' => $form,
            'dossierAgrement' => $dossierAgrement,
            'categoriesAnnuaire' => $categoriesAnnuaire
        ]);

    }


    #[Route('/dossier/defis/{id}', name: 'app_dossier_defis')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function defis(DossierAgrement $dossierAgrement, Request $request, EntityManagerInterface $em): Response
    {

        if ($request->isMethod('post')) {

            //Enregistrer les défis produits
            if($request->get('professionnels')) {
                foreach ($request->get('professionnels') as $key => $produit) {
                    $this->enregistrerDefi($key, $produit, 'professionnel', $request->get('professionnels-etat'.$key),  $dossierAgrement, $em);
                }
            }

            //Enregistrer les défis professionnels
            if($request->get('produits')) {
                foreach ($request->get('produits') as $key => $produit) {
                    $this->enregistrerDefi($key, $produit, 'produit', $request->get('produits-etat'.$key),  $dossierAgrement, $em);
                }
            }

            //Enregistrer le défi reutiliser
            foreach ($request->get('reutiliser') as $key => $produit) {
                $this->enregistrerDefi($key, $produit, 'reutiliser', $request->get('reutiliser-etat'.$key),  $dossierAgrement, $em);
            }


            //Enregistrer le défi promotion euskara
            foreach ($request->get('promotionEuskara') as $key => $produit) {
                $this->enregistrerDefi($key, $produit, 'promotionEuskara', $request->get('promotionEuskara-etat'.$key),  $dossierAgrement, $em);
            }


            //Enregistrer le défi accueil euskara
            foreach ($request->get('accueilEuskara') as $key => $produit) {
                $this->enregistrerDefi($key, $produit, 'accueilEuskara', $request->get('accueilEuskara-etat'.$key),  $dossierAgrement, $em);
            }


            /**************    ENREGISTREMENT   *******************/
            $em->persist($dossierAgrement);
            $em->flush();

        }

        return $this->renderForm('dossier/defis.html.twig', [
            'dossierAgrement' => $dossierAgrement
        ]);

    }

    #[Route('/dossier/vieDuReseau/{id}', name: 'app_dossier_vieDuReseau')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function vieDuReseau(DossierAgrement $dossierAgrement, Request $request, EntityManagerInterface $em, APITollboxController $APITollboxController): Response
    {
        $response = $APITollboxController->curlRequestDolibarr('GET', 'thirdparties');
        dump($response);
        $data =["skype"=> "bretele"];
        $responseBis = $APITollboxController->curlRequestDolibarr('PUT', 'thirdparties/622', $data);
        dump($responseBis);

        if ($request->isMethod('post')) {

            /******************************************************/
            /***************    FOURNISSEURS   ********************/
            //on sauvegarde l'ancienne liste des adresses pour les suppressions
            $tabFournisseursToDelete = $dossierAgrement->getFournisseurs()->toArray();

            if($request->get('dataFournisseurs')) {

                foreach ($request->get('dataFournisseurs') as $adresseJson) {
                    //on decode le json entier
                    $fournisseurObjet = json_decode($adresseJson);

                    if( !str_starts_with($fournisseurObjet->id, 'TEMP')){
                        //on récupère le fournisseur existant et on le retire du tableau
                        $fournisseur = $em->getRepository(Fournisseur::class)->find($fournisseurObjet->id);
                        if (($key = array_search($fournisseur, $tabFournisseursToDelete)) !== false) {
                            unset($tabFournisseursToDelete[$key]);
                        }
                    } else {
                        //création d'un nouveau fournisseur
                        $fournisseur = new Fournisseur();
                    }

                    //enregistrement
                    $fournisseur->updateFormJsonObject($fournisseurObjet);
                    $fournisseur->setDossierAgrement($dossierAgrement);
                    $em->persist($fournisseur);
                }
            }

            //On supprime les contacts qui ont été supprimés en front
            foreach ($tabFournisseursToDelete as $fournisseur){
                $dossierAgrement->removeFournisseur($fournisseur);
                $em->remove($fournisseur);
            }

            /******************************************************/
            /*****************  ECO-SYSTEME  **********************/

            foreach ($request->get('enargia') as $key => $produit) {
                $this->enregistrerDefi($key, $request->get('defi'.$key), 'enargia', 1,  $dossierAgrement, $em);
            }

            foreach ($request->get('paysBasqueAuCoeur') as $key => $produit) {
                $this->enregistrerDefi($key, $request->get('defi'.$key), 'paysBasqueAuCoeur', 1,  $dossierAgrement, $em);
            }

            foreach ($request->get('lantegiak') as $key => $produit) {
                $this->enregistrerDefi($key, $request->get('defi'.$key), 'lantegiak', 1,  $dossierAgrement, $em);
            }

            /**************    ENREGISTREMENT   *******************/
            $em->persist($dossierAgrement);
            $em->flush();

        }

        return $this->renderForm('dossier/vieReseau.html.twig', [
            'dossierAgrement' => $dossierAgrement
        ]);

    }


    public function enregistrerDefi($key,$produit, $type, $etat, DossierAgrement &$dossierAgrement, EntityManagerInterface $em){
        $defi = $em->getRepository(Defi::class)->find($key);
        if(!$defi){
            $defi = new Defi();
        }
        if($type == 'produit' or $type == 'professionnel'){
            if($produit != ''){
                $defi->setType($type);
                $defi->setValeur($produit);
                $defi->setEtat($etat);
                $dossierAgrement->addDefi($defi);
            } else {
                $dossierAgrement->removeDefi($defi);
            }

        } else {
            $defi->setType($type);
            $defi->setValeur($produit);
            $defi->setEtat($etat);
            $dossierAgrement->addDefi($defi);
        }

        return true;
    }
}
