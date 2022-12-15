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
    public function search(DolibarrController $crm, EntityManagerInterface $em, Request $request): JsonResponse
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
        return new JsonResponse('ok');
    }


    /*
     * [ENVOI DU DOSSIER AU CRM] etape 1
     *  Vérifier le dossier et qu'un tier n'est pas déjà dans le CRM
     */
    #[Route('/admin/dossier/check/{id}', name: 'app_admin_dossier_check')]
    #[ParamConverter('dossierAgrement', class: DossierAgrement::class)]
    public function checkDossier(DossierAgrement $dossierAgrement,Pool $pool, DolibarrController $crm, Request $request): Response
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
    public function envoiDossierCRM(DossierAgrement $dossierAgrement,
                                    Pool $pool,
                                    DolibarrController $crm,
                                    EntityManagerInterface $em,
                                    Request $request,
        $idExterne = '0'): Response
    {
        if($idExterne == '0'){
            $idTier = 0;
        } elseif(str_starts_with($idExterne,'CRM')) {
            $idTier = explode('CRM', $idExterne)[1];
            $result = $crm->updateTier($idTier);
            if($result){
                $this->addFlash('success', 'Tier '.$idTier.' mis à jour');
            }
            $dossierAgrement->setIdExterne($idTier);
        }

        /**** Enregistrer le tier dans le CRM
        $idExterne = $crm->postTier($dossierAgrement);
        $dossierAgrement->setIdExterne($idExterne);

        $em->persist($dossierAgrement);
        $em->flush();

        //**** Enregistrer le dirigeant en tant que contact
        $contactDirigeant = new Contact();
        $contactDirigeant->setPrenom($dossierAgrement->getPrenomDirigeant());
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
        $dossierAgrement->setIdAdherent($crm->postAdherent($dossierAgrement));

        //***** Documents
        foreach ($dossierAgrement->getDocuments() as $document){
        $crm->postDocument($document);
        }

        //***** Cotisation
        $crm->postCotisation($dossierAgrement);
         */

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
                ->attach($pdfAttach, sprintf('recu.pdf', date('d-m-Y')))
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
    private function envoiDefi(EntityManagerInterface $em, DolibarrController $crm, DossierAgrement $dossierAgrement){

        //***** Defis produits
        $defisProduits = $em->getRepository(Defi::class)->findBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'produit'
        ]);

        if(count($defisProduits) > 0){
            $defi = new Defi();
            $defi->setDossierAgrement($dossierAgrement);
            $defi->setType('produit');
            $defi->setEtat(false);

            foreach ($defisProduits as $defiProduit){
                $nomProduit = json_decode($defiProduit->getValeur())->text;
                $defi->setValeur($defi->getValeur().' '.$nomProduit);

                //Si un des défi est réalisé
                if($defiProduit->isEtat()){
                    $defi->setEtat(true);
                }
            }
            $crm->postDefi($defi);
        } else {

            //***** Sinon defis presta
            $defisPrestataires = $em->getRepository(Defi::class)->findBy([
                'dossierAgrement' => $dossierAgrement->getId(),
                'type' => 'professionnel'
            ]);

            if(count($defisPrestataires) > 0){
                $defi = new Defi();
                $defi->setDossierAgrement($dossierAgrement);
                $defi->setType('professionnel');
                $defi->setEtat(false);

                foreach ($defisPrestataires as $defiPresta){
                    $nomPrestataire = json_decode($defiPresta->getValeur())->text;
                    $defi->setValeur($defi->getValeur().' '.$nomPrestataire);

                    //Si un des défi est réalisé
                    if($defiPresta->isEtat()){
                        $defi->setEtat(true);
                    }
                }
                $crm->postDefi($defi);
            } else {
                //***** Sinon defi réutiliser
                $defiAccueil = $em->getRepository(Defi::class)->findOneBy([
                    'dossierAgrement' => $dossierAgrement->getId(),
                    'type' => 'reutiliser'
                ]);
                if($defiAccueil){
                    $crm->postDefi($defiAccueil);
                }

            }

        }

        //***** Defi accueil
        $defiAccueil = $em->getRepository(Defi::class)->findOneBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'accueilEuskara'
        ]);
        if($defiAccueil){
            $crm->postDefi($defiAccueil);
        }

        //***** Defi promotion
        $defiPromotion = $em->getRepository(Defi::class)->findOneBy([
            'dossierAgrement' => $dossierAgrement->getId(),
            'type' => 'promotionEuskara'
        ]);
        if($defiPromotion){
            $crm->postDefi($defiPromotion);
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
                $section->addText(
                    'Activité : ' ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addText(
                    'Adresse : '.json_decode($dossier->getAdressePrincipale())->text ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addText(
                    'Frais de dossier : '.$dossier->getFraisDeDossier().' €' ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addText(
                    'Cotisation : '.$dossier->getMontant().' €' ,
                    $paragraphFontStyle,
                    $paragraphStyleName);
                $section->addTextBreak(1);
                $section->addText(
                    'Défis : ',
                    $paragraphFontStyle,
                    $paragraphStyleName);

                $section->addTextBreak(1);
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
        $objWriter = IOFactory::createWriter($phpWord, 'ODText');

        // Create a temporal file in the system
        $fileName = 'ordre_du_jour-'.$date->format('d-m-Y').'.odt';
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
