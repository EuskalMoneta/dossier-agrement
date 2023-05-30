<?php

namespace App\Controller;

use App\Entity\CategorieAnnuaire;
use App\Entity\Contact;
use App\Entity\Defi;
use App\Entity\Document;
use App\Entity\DossierAgrement;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class GestionController extends AbstractController
{

    /* Appel en ajax de cette fonction,
    *  renvoi une liste de pros
    */
    #[Route('/searchPro', name: 'app_search_pro')]
    public function search(OdooController $crm, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $term = $request->get('q');
        $pros = $crm->searchProfessionnel($term);
        return new JsonResponse(["results" => $pros]);
    }

    /*
     * Stocke les fichiers envoyes par dropzone js
     * Zone de depot sur la partie front
     */
    #[Route('/send/ajax/file/{id}/{type}', name: 'app_send_ajax_file')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function sendFile(DossierAgrement $dossierAgrement, $type, Request $request, EntityManagerInterface $em): Response
    {
        $document = new Document();
        $document->setType($type);
        $document->setFile($request->files->get('file'));
        $document->upload();

        $dossierAgrement->addDocument($document);
        $em->persist($dossierAgrement);
        $em->flush();
        return new JsonResponse($document->getId());
    }

    /*
     * Stocke les fichiers envoyes par dropzone js
     * Zone de depot sur la partie front
     */
    #[Route('/remove/ajax/file', name: 'app_remove_ajax_file')]
    public function removeFile(Request $request, EntityManagerInterface $em): Response
    {
        $document = $em->find(Document::class, $request->get('idDocument'));
        $em->remove($document);
        $em->flush();
        return new JsonResponse($document->getId());
    }


    /*
     * [ENVOI DU DOSSIER AU CRM] etape 1
     *  Vérifier le dossier et qu'un tier n'est pas déjà dans le CRM
     */
    #[Route('/admin/dossier/check/{id}', name: 'app_admin_dossier_check')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function checkDossier(DossierAgrement $dossierAgrement, Pool $pool, OdooController $crm, Request $request): Response
    {
        $pros =[];
        $method = $request->isMethod('POST');
        if($method){
            $pros = $crm->searchProfessionnel($request->get('term'));
        }

        return $this->render('admin/checkDossier.html.twig', ['admin_pool' => $pool, 'dossier' => $dossierAgrement, 'pros' => $pros, 'method' => $method]);
    }

    /*
     * [ENVOI DU DOSSIER AU CRM] etape 2
     * Si l'id externe est à 0 c'est un nouveau dossier
     * Sinon on utilise le tier déjà existant
     *
     */
    #[Route('/admin/dossier/envoi/{id}/{idExterne}', name: 'app_admin_dossier_envoi')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function envoiDossierCRM(DossierAgrement        $dossierAgrement,
                                    Pool                   $pool,
                                    OdooController         $crm,
                                    EntityManagerInterface $em,
                                    Request                $request,
                                                           $idExterne = '0'): Response
    {
        if($idExterne == '0'){
            $idExterne = 0;
        } elseif(str_starts_with($idExterne,'CRM')) {
            //sinon on récupère l'id du tier
            $dossierAgrement->setIdExterne(explode('CRM', $idExterne)[1]);
        }

        //**** Enregistrer le tier dans le CRM, récupérer son ID
        $idExterne = $crm->postTier($dossierAgrement);
        $dossierAgrement->setIdExterne($idExterne);

        $em->persist($dossierAgrement);
        $em->flush();

        //**** Enregistrer le dirigeant en tant que contact
        $contactDirigeant = new Contact();
        $contactDirigeant->setPrenom($dossierAgrement->getPrenomDirigeant());
        $contactDirigeant->setCivilite($dossierAgrement->getCiviliteDirigeant());
        $contactDirigeant->setNom($dossierAgrement->getNomDirigeant());
        $contactDirigeant->setFonction($dossierAgrement->getFonctionDirigeant());
        $contactDirigeant->setTelephone($dossierAgrement->getTelephoneDirigeant());
        $contactDirigeant->setEmail($dossierAgrement->getEmailDirigeant());
        $contactDirigeant->setDossierAgrement($dossierAgrement);

        $crm->postContact($contactDirigeant);

        //**** Enregistrer les autres contacts
        foreach ($dossierAgrement->getContacts() as $contact){
        $crm->postContact($contact);
        }

        //***** Adresse(s) activité
        foreach ($dossierAgrement->getAdresseActivites() as $adresseActivite){
        $crm->postAdresseActivite($adresseActivite);
        }

        //***** Défis
        $this->envoiDefi($em, $crm, $dossierAgrement);

        //***** Adhérent
        $idAdherent = $crm->postAdherent($dossierAgrement);
        if ($idAdherent) {
            $dossierAgrement->setIdAdherent($idAdherent);

            //***** Cotisation
            $crm->postCotisation($dossierAgrement);

            //***** Documents
            foreach ($dossierAgrement->getDocuments() as $document){
                $crm->postDocument($document);
            }
        }

        //***** Compte numérique
        $crm->postBankUser($dossierAgrement);

        $em->persist($dossierAgrement);
        $em->flush();


        return $this->render('admin/envoiDossier.html.twig', ['admin_pool' => $pool, 'dossier' => $dossierAgrement]);
        /*$this->addFlash('success', 'Dossier envoyé sur le CRM avec succès ! ');
        return $this->redirectToRoute('admin_app_dossieragrement_edit', ['id'=> $dossierAgrement->getId()]);*/
    }

    /*
     * Envoi un email pour le prestataire, avec les autocollant et un reçu.
     */
    #[Route('/admin/dossier/email-prestataire/{id}', name: 'app_admin_email_prestataire')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function emailPrestatire(MailerInterface $mailer, Pdf $pdf, DossierAgrement $dossierAgrement,Pool $pool, Request $request): Response
    {
        $method = $request->isMethod('POST');
        if($method){

            $pdfAttach = $pdf->getOutputFromHtml(
                $this->renderView('admin/recu.html.twig', ['dossierAgrement' => $dossierAgrement]),[]
            );

            $email = (new TemplatedEmail())
                ->from('gestion@euskalmoneta.org')
                ->to($dossierAgrement->getEmailPrincipal())
                ->subject('Agrément eusko')
                ->attach($pdfAttach, sprintf('recu-%s.pdf', date('d-m-Y')))
                ->attachFromPath('../public/images/Autocollant-Eusko-Euskaraz.jpg')
                ->html("<p>
                        Egun on,<br /><br />  
                            Zure elkartea Euskal monetako azken onespen batzordean baimendua izan dela jakinarazten dizut .<br />  
                            Pegatin numerikoa eta dosier gastu ordainagiria loturik juntatzen dizkizut .<br /> <br /> 
                            
                            Je vous informe que votre association a bien été agréée à l'eusko lors du dernier comité d'agrément. Vous trouverez ci-joint l'autocollant numérique et le reçu de frais de dossier.<br /> <br /> 
                                    Laster arte,
                        </p>");
            ;

            $mailer->send($email);

        }

        return $this->render('admin/envoiEmailPrestataire.html.twig', ['admin_pool' => $pool, 'dossier' => $dossierAgrement, 'method' => $method]);
    }

    /*
     * Groupe puis envoi les défis sur le CRM
     */
    private function envoiDefi(EntityManagerInterface $em, OdooController $crm, DossierAgrement $dossierAgrement){

        //***** Défi "Trois produits locaux"
        $defisProduits = $em->getRepository(Defi::class)->findBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'produit'
        ]);
        if(count($defisProduits) > 0){
            $defi = new Defi();
            $defi->setDossierAgrement($dossierAgrement);
            $defi->setType('produit');
            $defi->setEtat(true);

            foreach ($defisProduits as $defiProduit){
                $nomProduit = $defiProduit->getValeur();
                $defi->setValeur($defi->getValeur().' '.$nomProduit.' : '.$defiProduit->getEtatReadable().'<br>');

                //Si un des produits est "A réaliser"
                if(!$defiProduit->isEtat()){
                    $defi->setEtat(false);
                }
            }
            $crm->postDefi($defi);
        }

        //***** Défi "Trois professionnels du réseau"
        $defisPrestataires = $em->getRepository(Defi::class)->findBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'professionnel'
        ]);
        if(count($defisPrestataires) > 0){
            $defi = new Defi();
            $defi->setDossierAgrement($dossierAgrement);
            $defi->setType('professionnel');
            $defi->setEtat(true);

            foreach ($defisPrestataires as $defiPresta){
                $nomPrestataire = json_decode($defiPresta->getValeur())->text;
                $defi->setValeur($defi->getValeur().' '.$nomPrestataire.' : '.$defiPresta->getEtatReadable().'<br>');

                //Si un des prestataires est "A réaliser"
                if(!$defiPresta->isEtat()){
                    $defi->setEtat(false);
                }
            }
            $crm->postDefi($defi);
        }

        //***** Défi "Réutiliser à titre personnel"
        $defiReutiliser = $em->getRepository(Defi::class)->findOneBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'reutiliser'
        ]);
        if($defiReutiliser){
            $crm->postDefi($defiReutiliser);
        }

        //***** Defi promotion
        $defiPromotion = $em->getRepository(Defi::class)->findOneBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'promotionEuskara'
        ]);
        if($defiPromotion && $defiPromotion->getValeur() !== 'Non renseigné'){
            if($defiPromotion->getValeur() === 'Déjà réalisé'){
                $defiPromotion->setEtat(1);
            } else {
                $defiPromotion->setEtat(0);
            }
            $crm->postDefi($defiPromotion);
        }

        //***** Defi accueil
        $defiAccueil = $em->getRepository(Defi::class)->findOneBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'accueilEuskara'
        ]);
        if($defiAccueil && $defiAccueil->getValeur() !== 'Non renseigné'){
            if($defiAccueil->getValeur() === 'Déjà réalisé'){
                $defiAccueil->setEtat(1);
            } else {
                $defiAccueil->setEtat(0);
            }
            $crm->postDefi($defiAccueil);
        }



        return true;
    }





    /**
     * Génère le brouillon de compte rendu au format odt
     * Prends une sélection de dossier via sonata admin en entrée
     * Retourne un fichier odt
     *
     * @param ProxyQueryInterface $query
     * @param AdminInterface $admin
     * @return Response
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function batchGenerationAction(ProxyQueryInterface $query, AdminInterface $admin): Response
    {
        $admin->checkAccess('edit');
        $admin->checkAccess('delete');

        $modelManager = $admin->getModelManager();

        $selectedModels = $query->execute();

        // do the merge work here

        // Create a new Word document
        $phpWord = new PhpWord();

        /* Note: any element you append to a document must reside inside of a Section. */


        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $formatter->setPattern('E d MMMM yyyy');
        $date = (new \DateTime('now'));
        // Adding an empty Section to the document...
        $section = $phpWord->addSection();

        $TitleFontStyle = 'TitleStyle';
        $phpWord->addFontStyle(
            $TitleFontStyle,
            array('name' => 'Tahoma', 'size' => 12, 'color' => '000000', 'bold' => true)
        );

        $blodFontStyle = 'BoldStyle';
        $phpWord->addFontStyle(
            $blodFontStyle,
            array('name' => 'Tahoma', 'size' => 10, 'color' => '000000', 'bold' => true)
        );

        $paragraphFontStyle = 'ParagraphStyle';
        $phpWord->addFontStyle(
            $paragraphFontStyle,
            array('name' => 'Tahoma', 'size' => 10, 'color' => '000000', 'bold' => false)
        );

        $paragraphCenterStyleName = 'pStyle';
        $phpWord->addParagraphStyle($paragraphCenterStyleName, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]);

        $paragraphStyleBold = 'pStylebold';
        $phpWord->addParagraphStyle($paragraphStyleBold, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::START,  'spaceAfter' => 100]);

        $paragraphStyleName = 'pStylenormal';
        $phpWord->addParagraphStyle($paragraphStyleName, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::START,  'spaceBefore' => 0, 'spaceAfter' => 0,'spacing' => 0]);

        $section->addText(
            'Ordre du jour réunion du comité d’agrément du '.$formatter->format($date),
            $TitleFontStyle,
            $paragraphCenterStyleName
        );

        // Adding Text element to the Section having font styled by default...
        $section->addText(
            'Présent-e-s :' ,
            $blodFontStyle,
            $paragraphStyleBold);

        $section->addText(
            'Excusé-e-s :' ,
            $blodFontStyle,
            $paragraphStyleBold);

        $section->addText(
            'Ordre du jour :' ,
            $blodFontStyle,
            $paragraphStyleBold);

        $section->addText(
            '1. Retour sur le dernier comité d’agrément' ,
            $paragraphFontStyle,
            $paragraphStyleBold);
        $section->addText(
            '2. Étude des dossiers de demande d’agrément' ,
            $paragraphFontStyle,
            $paragraphStyleBold);
        $section->addText(
            '3. Points Divers' ,
            $paragraphFontStyle,
            $paragraphStyleBold);

        $section->addTextBreak(2);
        $section->addText(
            '1. Retour sur le dernier comité d’agrément' ,
            $blodFontStyle,
            $paragraphStyleBold);
        $section->addTextBreak(2);

        $section->addText(
            '2. Étude des dossiers de demande d’agrément' ,
            $blodFontStyle,
            $paragraphStyleBold);

        $section->addTextBreak(1);


        try {
            /** @var DossierAgrement $dossier */
            foreach ($selectedModels as $dossier) {
                //$modelManager->update($selectedModel);

                $section->addText(
                    'Nom : '.$dossier->getLibelle() ,
                    $blodFontStyle,
                    $paragraphStyleBold);
                $section->addText(
                    'Type : '.$dossier->getType() ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                if($adresseActivite = $dossier->getAdresseActivites()->first()){
                    $section->addText(
                        'Activité : '.$adresseActivite->getDescriptifActivite(),
                        $paragraphFontStyle,
                        $paragraphStyleName);
                }

                $section->addText(
                    'Adresse : '.json_decode($dossier->getAdressePrincipale())->text ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addText(
                    'Frais de dossier : '.$dossier->getFraisDeDossier().' €' ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addText(
                    'Cotisation : '.$dossier->getMontant().' € ('.
                    ($dossier->getTypeCotisation() == 'solidaire'?"Cotisation solidaire":"Cotisation de base").')' ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addTextBreak(1);
                $section->addText(
                    'Défis : ',
                    $paragraphFontStyle,
                    $paragraphStyleName);

                $produitsLocaux = $dossier->getDefisByType('produit');
                if(count($produitsLocaux) >0){
                    $section->addText(
                        "Trois produits locaux",
                        $paragraphFontStyle,
                        $paragraphStyleName);

                    /** @var Defi $defi */
                    foreach ($produitsLocaux as $defi){
                        $section->addListItem($defi->getValeur().' - '.$defi->getEtatReadable(),
                            0,
                            $paragraphFontStyle,
                            null,
                            $paragraphStyleName
                            );

                    }
                }
                $section->addTextBreak();

                $pros = $dossier->getDefisByType('professionnel');
                if(count($pros) >0){
                    $section->addText(
                        "Trois prestataires du réseau",
                        $paragraphFontStyle,
                        $paragraphStyleName);
                    /** @var Defi $defi */
                    foreach ($pros as $defi){
                        $note ='';
                        if(json_decode($defi->getValeur())->note !== ''){
                            $note = ' - '.json_decode($defi->getValeur())->note;
                        }
                        $section->addListItem(json_decode($defi->getValeur())->text.$note.' - '.$defi->getEtatReadable(),
                            0,
                            $paragraphFontStyle,
                            null,
                            $paragraphStyleName
                        );
                    }
                }
                $section->addTextBreak();

                $reutilisers = $dossier->getDefisByType('reutiliser');
                if(count($reutilisers) >0 && $reutilisers[0]->getValeur() != '' && $reutilisers[0]->getValeur() != 'Non renseigné' ){
                    $section->addText(
                        "Réutiliser à titre personnel",
                        $paragraphFontStyle,
                        $paragraphStyleName);
                    /** @var Defi $defi */
                    foreach ($reutilisers as $defi){
                        $section->addListItem($defi->getValeur().' - '.$defi->getEtatReadable(),
                            0,
                            $paragraphFontStyle,
                            null,
                            $paragraphStyleName
                        );
                    }
                }
                $section->addTextBreak();

                $promotion = $dossier->getDefisByType('promotionEuskara');
                if(count($promotion) >0 && $promotion[0]->getValeur() != '' && $promotion[0]->getValeur() != 'Non renseigné'){
                    $section->addText(
                        "Affichage en euskara",
                        $paragraphFontStyle,
                        $paragraphStyleName);
                    /** @var Defi $defi */
                    foreach ($promotion as $defi){
                        $section->addListItem($defi->getValeur().' - '.$defi->getEtatReadable(),
                            0,
                            $paragraphFontStyle,
                            null,
                            $paragraphStyleName
                        );
                    }
                }
                $section->addTextBreak();

                $accueil = $dossier->getDefisByType('accueilEuskara');
                if(count($accueil) >0 && $accueil[0]->getValeur() != 'Non renseigné'){
                    $section->addText(
                        "Accueil en euskara",
                        $paragraphFontStyle,
                        $paragraphStyleName);
                    /** @var Defi $defi */
                    foreach ($accueil as $defi){
                        $section->addListItem($defi->getValeur().' - '.$defi->getEtatReadable(),
                            0,
                            $paragraphFontStyle,
                            null,
                            $paragraphStyleName
                        );
                    }
                }
                $section->addTextBreak();

                $section->addText(
                    'Note : '.$dossier->getNote(),
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addTextBreak();

                $section->addText(
                    'Commentaires : ',
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addText(
                    'Décision : ',
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addTextBreak(2);


            }
        } catch (\Exception $e) {
        }

        $section->addTextBreak(2);

        $section->addText(
            '3. Points Divers' ,
            $blodFontStyle,
            $paragraphStyleName);
        // Saving the document as OOXML file...
        //$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

        // Create a temporal file in the system
        $fileName = 'ordre_du_jour-'.$date->format('d-m-Y').'.docx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        // Write in the temporal filepath
        $objWriter->save($temp_file);

        // Send the temporal file as response (as an attachment)
        $response = new BinaryFileResponse($temp_file);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;

    }
}
