<?php

namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\CategorieAnnuaire;
use App\Entity\Contact;
use App\Entity\Defi;
use App\Entity\Document;
use App\Entity\DossierAgrement;
use App\Entity\Fournisseur;
use App\Entity\ReductionAdhesion;
use App\Entity\WebHookEvent;
use App\Form\CoordonneesFormType;
use App\Form\OptionsTechniqueProFormType;
use App\Form\SignatureElectroniqueFormType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WiziYousignClient\WiziSignClient;

class DossierController extends AbstractController
{

    #[Route('/dossier/nouveau', name: 'app_dossier_nouveau')]
    public function nouveauDossier(Request $request, EntityManagerInterface $em, DolibarrController $crm): Response
    {

        if($request->isMethod('post')){
            $this->synchroCategories($crm, $em);
            $dossierAgrement = new DossierAgrement();
            $dossierAgrement->setCreated(new \DateTime());
            $dossierAgrement->setLibelle($request->get('nom-dossier'));
            $dossierAgrement->setType($request->get('type-dossier'));

            $dossierAgrement->setUtilisateur($this->getUser());

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
        $categoriesAnnuaireEskuz = $em->getRepository(CategorieAnnuaire::class)->findBy(['type' => 'eskuz']);

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

                    //gestion des categories de l'annuaire
                    $adresse->cleanCategoriesAnnuaireEskuz();
                    foreach ($adresseObjet->categoriesAnnuaireEskuz as $categorieId){
                        $categorie = $em->getRepository(CategorieAnnuaire::class)->find($categorieId);
                        if($categorie){
                            $adresse->addCategoriesAnnuaireEskuz($categorie);
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


        return $this->render('dossier/coordonnees.html.twig', [
            'form' => $form,
            'dossierAgrement' => $dossierAgrement,
            'categoriesAnnuaire' => $categoriesAnnuaire,
            'categoriesAnnuaireEskuz' => $categoriesAnnuaireEskuz
        ]);

    }


    #[Route('/dossier/defis/{id}', name: 'app_dossier_defis')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function defis(DossierAgrement $dossierAgrement, Request $request, EntityManagerInterface $em): Response
    {

        if ($request->isMethod('post')) {

            //Enregistrer les défis professionnels
            if($request->get('professionnels')) {
                foreach ($request->get('professionnels') as $key => $value) {
                    $selectValue = json_decode($value, true);

                    //on vérifie que le pro a un ID sinon c'est une suppression
                    if(isset($selectValue['id'])){
                        $selectValue['note'] = $request->get('professionnels-note'.$key);
                        $value = json_encode($selectValue);
                    } else {
                        $value = '';
                    }
                    $this->enregistrerDefi($key, $value, 'professionnel', $request->get('professionnels-etat'.$key),  $dossierAgrement, $em);
                }
            }

            //Enregistrer les défis produits
            if($request->get('produits')) {
                foreach ($request->get('produits') as $key => $value) {
                    $this->enregistrerDefi($key, $value, 'produit', $request->get('produits-etat'.$key),  $dossierAgrement, $em);
                }
            }

            //Enregistrer le défi reutiliser
            foreach ($request->get('reutiliser') as $key => $value) {
                $this->enregistrerDefi($key, $value, 'reutiliser', $request->get('reutiliser-etat'.$key),  $dossierAgrement, $em);
            }

            //Enregistrer le défi promotion euskara
            foreach ($request->get('promotionEuskara') as $key => $value) {
                $this->enregistrerDefi($key, $value, 'promotionEuskara', $request->get('promotionEuskara-etat'.$key),  $dossierAgrement, $em);
            }

            //Enregistrer le défi accueil euskara
            foreach ($request->get('accueilEuskara') as $key => $value) {
                $this->enregistrerDefi($key, $value, 'accueilEuskara', $request->get('accueilEuskara-etat'.$key),  $dossierAgrement, $em);
            }

            /**************    ENREGISTREMENT   *******************/
            $em->persist($dossierAgrement);
            $em->flush();

            return $this->redirectToRoute('app_dossier_vieDuReseau', ['id' => $dossierAgrement->getId()]);

        }

        return $this->render('dossier/defis.html.twig', [
            'dossierAgrement' => $dossierAgrement
        ]);

    }

    #[Route('/dossier/vieDuReseau/{id}', name: 'app_dossier_vieDuReseau')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function vieDuReseau(DossierAgrement $dossierAgrement, Request $request, EntityManagerInterface $em): Response
    {

        if ($request->isMethod('post')) {

            /******************************************************/
            /***************    FOURNISSEURS   ********************/
            //on sauvegarde l'ancienne liste des adresses pour les suppressions
            $tabFournisseursToDelete = $dossierAgrement->getFournisseurs()->toArray();

            if($request->get('dataFournisseurs')) {

                foreach ($request->get('dataFournisseurs') as $adresseJson) {
                    //on decode le json entier
                    $fournisseurObjet = json_decode($adresseJson);

                    if( str_starts_with($fournisseurObjet->id, 'TEMP')){
                        //création d'un nouveau fournisseur
                        $fournisseur = new Fournisseur();
                    } elseif( str_starts_with($fournisseurObjet->id, 'CRM')){
                        $fournisseur = new Fournisseur();
                        $fournisseur->setIdExterne(substr($fournisseurObjet->id, strlen("CRM")));

                    } else {
                        //on récupère le fournisseur existant et on le retire du tableau
                        $fournisseur = $em->getRepository(Fournisseur::class)->find($fournisseurObjet->id);
                        if (($key = array_search($fournisseur, $tabFournisseursToDelete)) !== false) {
                            unset($tabFournisseursToDelete[$key]);
                        }
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

            foreach ($request->get('enargia') as $key => $value) {
                $this->enregistrerDefi($key, $request->get('defi'.$key), 'enargia', 1,  $dossierAgrement, $em);
            }

            foreach ($request->get('paysBasqueAuCoeur') as $key => $value) {
                $this->enregistrerDefi($key, $request->get('defi'.$key), 'paysBasqueAuCoeur', 1,  $dossierAgrement, $em);
            }

            foreach ($request->get('lantegiak') as $key => $value) {
                $this->enregistrerDefi($key, $request->get('defi'.$key), 'lantegiak', 1,  $dossierAgrement, $em);
            }

            /**************    ENREGISTREMENT   *******************/
            $em->persist($dossierAgrement);
            $em->flush();

            return $this->redirectToRoute('app_dossier_adhesion', ['id' => $dossierAgrement->getId()]);
        }

        return $this->render('dossier/vieReseau.html.twig', [
            'dossierAgrement' => $dossierAgrement
        ]);

    }

    #[Route('/dossier/adhesion/{id}', name: 'app_dossier_adhesion')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function adhesion(DossierAgrement $dossierAgrement, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OptionsTechniqueProFormType::class, $dossierAgrement);

        $reductionsAdhesion = $em->getRepository(ReductionAdhesion::class)->findBy(['visible' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            //Ajout des réductions qui ont été cochées
            $dossierAgrement->cleanReductionsAdhesion();
            if($request->get('reductions')){
                foreach ($request->get('reductions') as $idReduction){
                    $reduction = $em->getRepository(ReductionAdhesion::class)->find($idReduction);
                    $dossierAgrement->addReductionsAdhesion($reduction);
                }
            }

            //suppression des documents
            if($request->get('docToDelete')){
                foreach ($request->get('docToDelete') as $documentId){
                    $em->remove($em->getRepository(Document::class)->find($documentId));
                }
            }

            /**************    ENREGISTREMENT   *******************/
            $em->persist($dossierAgrement);
            $em->flush();

            return $this->redirectToRoute('app_dossier_signature_electronique', ['id' => $dossierAgrement->getId()]);
        }

        return $this->render('dossier/adhesion.html.twig', [
            'form' => $form,
            'dossierAgrement' => $dossierAgrement,
            'reductionsAdhesion' => $reductionsAdhesion
        ]);

    }


    #[Route('/dossier/signature/{id}', name: 'app_dossier_signature_electronique')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function signatureElectronique(DossierAgrement $dossierAgrement, SessionInterface $session, Pdf $pdf, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SignatureElectroniqueFormType::class, $dossierAgrement);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /**************    ENREGISTREMENT   *******************/
            $em->persist($dossierAgrement);
            $em->flush();

            if(!$request->get('sauvegardeSimple')){
                //on démarre le client YouSign
                $youSignClient = new WiziSignClient($_ENV['YOUSIGN_API_KEY'], $_ENV['YOUSIGN_MODE']);

                //Création d'un identifiant unique qui permet de récupérer le webHook yousign dans la vue
                $identifiantWebHook = time();

                //Création du webHook
                $webHook = new WebHookEvent();
                $webHook->setIdentifiant($identifiantWebHook);

                //etape 1 init
                //pour tester, besoin d'un ngrok, remplacer generateURL
                $responseProcedure = $youSignClient->AdvancedProcedureCreate(
                    [
                        'start'=> false,
                        'name' => 'Signature prélèvement SEPA',
                        'description'=> 'SEPA'
                    ],
                    true,
                    'GET',
                    //'http://8629-2a01-e0a-5fa-c480-126c-e925-48a1-8f61.ngrok.io/eusko/dossier-agrement/public/index.php/webhook',
                    $this->generateUrl('yousign_web_hook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    $identifiantWebHook
                );

                //etape 2 fichier à signer
                $pdf->generateFromHtml($this->renderView('sepa/modeleSepa.html.twig', ['dossierAgrement' => $dossierAgrement]), '/tmp/sepa-'.$identifiantWebHook.'.pdf' );
                $responseFile = $youSignClient->AdvancedProcedureAddFile('/tmp/sepa-'.$identifiantWebHook.'.pdf', 'sepa.pdf');

                //etape 3 ajout signataire
                $response = $youSignClient->AdvancedProcedureAddMember(
                    $dossierAgrement->getPrenomSignature(),
                    $dossierAgrement->getNomSignature(),
                    $dossierAgrement->getEmailPrincipal(),
                    $dossierAgrement->getTelephoneSignature()
                );
                $member = json_decode($response);

                //etape 4 position et contenu de la signature
                $response = $youSignClient->AdvancedProcedureFileObject(
                    "150,235,460,335",
                    1,
                    "Lu et approuvé",
                    "Signé par ".$dossierAgrement->getPrenomSignature()." ".$dossierAgrement->getNomSignature(),
                    "Signé par ".$dossierAgrement->getPrenomSignature()." ".$dossierAgrement->getNomSignature());

                //etape 5 lancement de la procédure
                $response = $youSignClient->AdvancedProcedurePut();
                $status = json_decode($response)->status;
                if($status == 'active'){
                    //on enregistre l'id du fichier pour le récuperer signé plus tard
                    $webHook->setFile(json_decode($responseFile)->id);
                    $em->persist($webHook);
                    $em->flush();

                    //On sauvegarde en session l'ID du webHook
                    $session->set('idWebHookEvent', $webHook->getId());
                } else {
                    $this->addFlash('warning', 'Erreur lors de la création de la signature électronique');
                    $identifiantWebHook = 0;
                }

                return $this->render('dossier/signatureYousign.html.twig', [
                    'memberToken' => $member->id,
                    'webHook' => $identifiantWebHook,
                    'dossierAgrement' => $dossierAgrement
                ]);
            } else {
                return $this->redirectToRoute('app_dossier_fin', ['id' => $dossierAgrement->getId()]);
            }

        }

        return $this->render('dossier/signatureCoordonnes.html.twig', [
            'form' => $form,
            'dossierAgrement' => $dossierAgrement
        ]);

    }

    #[Route('/dossier/fin/{id}', name: 'app_dossier_fin')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function fin(DossierAgrement $dossierAgrement, SessionInterface $session, Request $request, EntityManagerInterface $em): Response
    {

        if($session->get('idWebHookEvent')){
            $webHook = $em->getRepository(WebHookEvent::class)->find($session->get('idWebHookEvent'));
            $youSignClient = new WiziSignClient($_ENV['YOUSIGN_API_KEY'], $_ENV['YOUSIGN_MODE']);
            $file = $youSignClient->downloadSignedFile($webHook->getFile(), 'base64');

            $filename_path = time().'-sepa'.".pdf";
            $decoded=base64_decode($file);
            file_put_contents(__DIR__.'/../../public/uploads/sepa/'.$filename_path, $decoded);

            $document = new Document();
            $document->setType('sepa');
            $document->setPath($filename_path);

            $dossierAgrement->addDocument($document);

            $em->persist($dossierAgrement);
            $em->flush();

            $session->remove('idWebHookEvent');
        }

        return $this->render('dossier/fin.html.twig', [ 'dossierAgrement' => $dossierAgrement ]);
    }


    #[Route('/webhook', name: 'yousign_web_hook')]
    public function webHook(EntityManagerInterface $em, Request $request)
    {
        //On récupère les headers de yousign pour associer le webhook à la bonne procédure
        $identifiantHook = $request->headers->get('x-custom-header');
        $evtName = $request->headers->get('x-yousign-event-name');

        $webHook = $em->getRepository(WebHookEvent::class)->findOneBy(['identifiant'=> $identifiantHook]);

        //si c'est l'evt de fin on change le statut du webHook interne
        if($evtName == 'member.finished'){
            $webHook->setStatut('finished');
        } else {
            $webHook->setStatut('started');
        }

        //enregistrement
        $em->persist($webHook);
        $em->flush();

        return new Response();
    }

    #[Route('/webhook/ajax', name: 'ajax_yousign_webhook')]
    public function ajaxResponse(EntityManagerInterface $em, Request $request)
    {
        //Toutes les 5 secondes on vérifie si le webhook a changé de statut, si oui on envoi le signal ok à la vue.
        $webHook = $em->getRepository(WebHookEvent::class)->findOneBy(['identifiant'=> $request->get('name')]);

        if($webHook->getStatut() =='finished'){
            return new JsonResponse('ok');
        } else {
            return new JsonResponse('en attente');
        }
    }

    /**
     *
     * Enregistre/met à jour les défis en base de données
     *
     * @param $key integer identifiant du défi s'il existe déjà en base
     * @param $value
     * @param $type
     * @param $etat
     * @param DossierAgrement $dossierAgrement
     * @param EntityManagerInterface $em
     *
     * @return bool
     */
    public function enregistrerDefi($key, $value, $type, $etat, DossierAgrement &$dossierAgrement, EntityManagerInterface $em){
        $defi = $em->getRepository(Defi::class)->find($key);
        if(!$defi){
            $defi = new Defi();
        }
        if($type == 'produit' or $type == 'professionnel'){
            if($value != ''){
                $defi->setType($type);
                $defi->setValeur($value);
                $defi->setEtat($etat);
                $dossierAgrement->addDefi($defi);
            } else {
                $dossierAgrement->removeDefi($defi);
            }

        } else {
            $defi->setType($type);
            $defi->setValeur($value);
            $defi->setEtat($etat);
            $dossierAgrement->addDefi($defi);
        }

        return true;
    }

    /*
     * Permet de récupérer la liste des catégories de l'annuaire + eskuz esku
     * Mettre à jour les libellés ou créer les nouvelles catégories
     */
    public function synchroCategories(DolibarrController $crm, EntityManagerInterface $em): bool
    {

        $categories = $crm->getCategoriesAnnuaire();

        foreach ($categories as $categorie){
            $cat = $em->getRepository(CategorieAnnuaire::class)->findOneBy(['idExterne' => $categorie['idExterne']]);
            if($cat){
                $cat->setLibelle($categorie['libelle']);
            } else {
                $cat = new CategorieAnnuaire();
                $cat->setType('eusko');
                $cat->setLibelle($categorie['libelle']);
                $cat->setIdExterne($categorie['idExterne']);
            }
            $em->persist($cat);
        }

        $em->flush();

        $categories = $crm->getCategoriesEskuzEsku();

        foreach ($categories as $categorie){
            $cat = $em->getRepository(CategorieAnnuaire::class)->findOneBy(['idExterne' => $categorie['idExterne']]);
            if($cat){
                $cat->setLibelle($categorie['libelle']);
            } else {
                $cat = new CategorieAnnuaire();
                $cat->setType('eskuz');
                $cat->setLibelle($categorie['libelle']);
                $cat->setIdExterne($categorie['idExterne']);
            }
            $em->persist($cat);
        }

        $em->flush();

        return true;
    }
}
