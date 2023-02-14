<?php

namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\Contact;
use App\Entity\Defi;
use App\Entity\Document;
use App\Entity\DossierAgrement;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class DolibarrController extends AbstractController implements CRMInterface
{

    private $dolibarr_url;
    private $api_token_dolibarr;
    private $logger;

    private $cyclos_url;
    private $cyclos_user;
    private $cyclos_pass;

    public function __construct(LoggerInterface $logger)
    {
        $this->dolibarr_url = $_ENV['API_DOLIBARR_URL'];
        $this->api_token_dolibarr = $_ENV['API_TOKEN_DOLIBARR'];

        $this->cyclos_url = $_ENV['API_CYCLOS_URL'];
        $this->cyclos_user = $_ENV['API_CYCLOS_USER'];
        $this->cyclos_pass = $_ENV['API_CYCLOS_PASS'];

        $this->logger = $logger;
    }

    public function postBankUser(DossierAgrement $dossierAgrement):int
    {

        //On vérifie si aucun utilisateur avec ce login existe.
        $responseExist = $this->curlRequestCyclos('POST', '/user/search', [
            'keywords'=> $dossierAgrement->getCodePrestataire(),
            ['userStatus'=> ['ACTIVE', 'BLOCKED', 'DISABLED']]
        ]);
        if($responseExist['httpcode'] == 200) {
            if ($responseExist['data']->result->totalCount==0){

                //Préparation et enregistrement de l'utilisateur
                $group = $_ENV['ADHERENTS_SANS_COMPTE'];
                $status = 'INACTIVE';
                if($dossierAgrement->isCompteNumeriqueBool()) {
                    $group = $_ENV['ADHERENTS_PRESTATAIRES'];
                    $status = 'ACTIVE';
                }elseif ($dossierAgrement->isPaiementViaEuskopay()){
                    $group = $_ENV['ADHERENTS_PRESTATAIRES_AVEC_PAIEMENT_SMARTPHONE'];
                    $status = 'ACTIVE';
                }

                $data = [
                    'group'=> $group,
                    'name'=> $dossierAgrement->getDenominationCommerciale(),
                    'username'=> $dossierAgrement->getCodePrestataire(),
                    'skipActivationEmail'=> True,
                ];
                $responseUser = $this->curlRequestCyclos('POST', '/user/register', $data);

                if($responseUser['httpcode'] == 200) {

                    //si l'utilisateur est actif, changer son status
                    if($status == 'ACTIVE'){
                        $data = [
                            'user'=> $responseUser['data']->result->user->id,
                            'status'=> 'ACTIVE',
                        ];
                        $responseActivation = $this->curlRequestCyclos('POST', '/userStatus/changeStatus', $data);
                        if($responseActivation['httpcode'] == 200) {
                            $this->addFlash('success', "Utilisateur cyclos ajouté avec succés.");
                        } else {
                            $this->addFlash('danger', "L'utilisateur cyclos n'a pas pu être activé.");
                        }
                    }
                } else{
                    $this->addFlash('danger', "L'utilisateur cyclos n'a pas été créé.");
                }

            }else{
                $this->addFlash('warning', "Utilisateur déjà présent dans Cyclos, le compte cyclos n'a pas été créé.");
            }
        }


        return true;
    }

    /**
     * Makes a cUrl request for cyclos
     *
     * @param $method
     * @param $link
     * @param string $data
     * @return array
     */
    private function curlRequestCyclos($method, $link,  $data = '')
    {
        $curlCyclos = curl_init();
        curl_setopt($curlCyclos, CURLOPT_URL, $this->cyclos_url.$link);
        curl_setopt($curlCyclos, CURLOPT_COOKIESESSION, true);
        curl_setopt($curlCyclos, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlCyclos, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlCyclos, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlCyclos, CURLOPT_CUSTOMREQUEST, $method);

        if($method == 'POST' or $method == 'PUT' or $method == 'PATCH'){
            curl_setopt($curlCyclos, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curlCyclos, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($this->cyclos_user.':'.$this->cyclos_pass),
                    'Content-Length: ' . strlen(json_encode($data)))
            );
        } else {
            curl_setopt($curlCyclos, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($this->cyclos_user.':'.$this->cyclos_pass),
                )
            );
        }

        $responseLogin = json_decode(curl_exec($curlCyclos));
        $http_status = curl_getinfo($curlCyclos, CURLINFO_HTTP_CODE);
        curl_close($curlCyclos);


        $token = '';
        if($http_status != 200){
            $this->addFlash('danger', "Connexion à Cyclos impossible, vérifier vos paramètres user, pass et URL de l'API.");
            return ['data' => 'bad credential', 'httpcode' =>'500'];
        }

        return ['data' => $responseLogin, 'httpcode' => $http_status];
    }

    /**
     * Makes a cUrl request
     *
     * @param $method
     * @param $link
     * @param string $data
     * @param string $token
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function curlRequestDolibarr($method, $link,  $data = '', $token ='')
    {
        if($token == '') {
            $token = $this->api_token_dolibarr;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->dolibarr_url.$link);
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if($method == 'POST' or $method == 'PUT' or $method == 'PATCH'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'DOLAPIKEY: ' . $token,
                    'Content-Length: ' . strlen(json_encode($data)))
            );
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'DOLAPIKEY: ' . $token)
            );
        }

        $return = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return ['data' => json_decode($return), 'httpcode' => $http_status];
    }


    public function getCategoriesAnnuaire():array
    {
        $tabCategoriesAnnuaire = [];
        $this->getCategoriesChildren('444', $tabCategoriesAnnuaire);
        return $tabCategoriesAnnuaire;
    }




    public function searchProfessionnel($term):array
    {
        $tabPro = [];

        $url = "thirdparties?sortfield=t.nom&sqlfilters=".urlencode("(t.status:=:1) and (t.nom:like:'%".$term."%')");
        $responsePro = $this->curlRequestDolibarr('GET', $url);

        if($responsePro['httpcode'] == 200){
            foreach ($responsePro['data'] as $pro){
                $status = '';
                switch ($pro->client){
                    case 0:
                        $status = 'Ni prestataire agréé, ni prospect';
                        break;
                    case 1:
                        $status = 'Prestataire agréé';
                        break;
                    case 2:
                        $status = 'Prospect';
                        break;
                    case 3:
                        $status = 'Prospect / Prestataire agréé';
                        break;
                }

                $adresse = $this->transformAdresseIntoJSON(
                    $pro->address.' '.$pro->zip.' '.$pro->town,
                    $pro->town,
                    '',
                    '',
                    $pro->address,
                    $pro->zip,
                    ''
                );

                if($pro->client)
                    $tabPro[] = [
                        'id' => 'CRM'.$pro->id,
                        'text' => $pro->name,
                        'prenom' => $pro->firstname,
                        'nom' => $pro->lastname,
                        'entreprise' => $pro->name,
                        'activite' => '',
                        'telephone' => $pro->phone,
                        'email' => $pro->email,
                        'adresse' => $adresse,
                        'commentaires' => $pro->note_private,
                        'status' => $status,
                    ];
            }
        }
        return $tabPro;
    }

    public function transformAdresseIntoJSON($text, $town, $lng, $lat, $address, $postcode, $selected){

        return json_encode( [
            'text' => $text,
            'id' => $town,
            'lng' => $lng,
            'lat' => $lat,
            'address' => $address,
            'postcode' => $postcode,
            'selected' => $selected
        ]);
    }


    public function getCategoriesEskuzEsku():array
    {
        $tabCategoriesEskuzEsku = [];
        $this->getCategoriesChildren('376', $tabCategoriesEskuzEsku);
        return $tabCategoriesEskuzEsku;
    }

    /*
     * Fonction récursive qui récupère l'ensemble des sous catégories d'un index donné.
     * Prend l'index et un tableau par référence pour les résultats
     */
    private function getCategoriesChildren($id_parent, &$tabCategories): bool
    {
        /**
         * $tabCategories[] = [
        'idExterne' => $categorie2->id,
        'libelle' => $libelleArbo.explode('/', $categorie2->label)[1]
        ];
         */
        $libelleArbo = '';
        $reponseCategoriesNiv1 = $this->curlRequestDolibarr('GET', 'categories?limit=200&type=contact&sqlfilters=(t.fk_parent:=:'.$id_parent.')');
        if($reponseCategoriesNiv1['httpcode'] == 200) {
            if(count($reponseCategoriesNiv1['data']) != 0) {
                foreach ($reponseCategoriesNiv1['data'] as $categorie) {

                    $reponseCategoriesNiv2 = $this->curlRequestDolibarr('GET', 'categories?limit=200&type=contact&sqlfilters=(t.fk_parent:=:'.$categorie->id.')');
                    if($reponseCategoriesNiv2['httpcode'] == 200) {
                        if (count($reponseCategoriesNiv2['data']) != 0) {
                            foreach ($reponseCategoriesNiv2['data'] as $categorie2) {

                                $libelleArbo = explode('/', $categorie->label)[1].' >'.explode('/', $categorie2->label)[1];
                                $reponseCategoriesNiv3 = $this->curlRequestDolibarr('GET', 'categories?limit=200&type=contact&sqlfilters=(t.fk_parent:=:'.$categorie2->id.')');
                                if($reponseCategoriesNiv3['httpcode'] == 200) {
                                    if (count($reponseCategoriesNiv3['data']) != 0) {
                                        foreach ($reponseCategoriesNiv3['data'] as $categorie3) {
                                            dump($categorie3);
                                            $tabCategories[] = [
                                                'idExterne' => $categorie3->id,
                                                'libelle' => $libelleArbo.' >'.explode('/', $categorie3->label)[1]
                                            ];
                                        }
                                    }
                                } else{
                                    $tabCategories[] = [
                                        'idExterne' => $categorie2->id,
                                        'libelle' => $libelleArbo
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }


        return false;
    }

    public function updateTier($id): bool
    {
        return true;
    }


    public function postAdresseActivite(AdresseActivite $adresseActivite): bool
    {

        $data = $this->transformAdresseActivite($adresseActivite);
        $reponseCategories = $this->curlRequestDolibarr('POST', 'contacts', $data);
        if($reponseCategories['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout de l'adresse d'activité : ".$adresseActivite->getNom());
            return false;
        }

        /*
         * Ajouter les catégories contact associées => ne marche pas
         * foreach ($adresseActivite->getCategoriesAnnuaire() as $categorieAnnuaire){
            $data = [
                'id' => $categorieAnnuaire->getIdExterne(),
            ];
            $reponseLiaison = $this->curlRequestDolibarr('PST', 'categories/'.$reponseCategories['data'].'/categories', $data);
            dump($reponseLiaison);
        }*/

        return true;
    }

    public function postContact(Contact $contact): int
    {
        $data = $this->transformContact($contact);
        $reponseCategories = $this->curlRequestDolibarr('POST', 'contacts', $data);
        if($reponseCategories['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout du contact : ".$contact->getNom());
            return false;
        }
        return true;
    }


    public function postTier(DossierAgrement $dossierAgrement): int
    {
        //Préparer les données de la requête
        $data = $this->transformTier($dossierAgrement);

        if($dossierAgrement->getIdExterne() > 0){
            //Si le tier existe déjà, on fait une mise à jour
            $reponseTier = $this->curlRequestDolibarr('PUT', 'thirdparties/'.$dossierAgrement->getIdExterne(), $data);
            if($reponseTier['httpcode'] != 200) {
                $this->addFlash("danger", "Erreur lors de la mise à jour du tier dans dolibarr.");
                return false;
            }
            return $reponseTier['data']->id;
        } else {
            //sinon on ajoute un nouveau tier
            $reponseTier = $this->curlRequestDolibarr('POST', 'thirdparties', $data);
            if($reponseTier['httpcode'] != 200) {
                $this->addFlash("danger", "Erreur lors de l'ajout du tier dans dolibarr.");
                return false;
            }
            return $reponseTier['data'];
        }

    }

    public function postDefi(Defi $defi): int
    {
        $data = $this->transformDefi($defi);
        $reponseCategories = $this->curlRequestDolibarr('POST', 'agendaevents', $data);
        if($reponseCategories['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout du defi : ".$defi->getValeur());
            return false;
        }

        return true;
    }

    public function postDocument(Document $document):int
    {
        $data = $this->transformDocument($document);
        $reponse = $this->curlRequestDolibarr('POST', 'documents/upload', $data);
        if($reponse['httpcode'] != 200) {
            //$this->addFlash("danger","Erreur lors de l'ajout du document : ".$reponse['data']->error->message);
            $this->addFlash("danger","Erreur lors de l'ajout du document : ".$document->getPath());
            return false;
        }

        return true;
    }

    public function postAdherent(DossierAgrement $dossierAgrement):int
    {
        $data = $this->transformAdherent($dossierAgrement);
        $reponse = $this->curlRequestDolibarr('POST', 'members', $data);
        if($reponse['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout de l'adhérent : ".$dossierAgrement->getCodePrestataire());
            $this->addFlash("danger",$reponse['data']->error->message);
            return false;
        }

        return $reponse['data'];
    }

    public function postCotisation(DossierAgrement $dossierAgrement): int{

        //Cotisation gratuite fin du mois
        $cotisationGratuite = $this->transformCotisation(
            (new \DateTime()),
            (new \DateTime())->modify("last day of this month"),
            0,
            "Adhésion/cotisation".date('Y'),
            $dossierAgrement
        );
        $reponse = $this->curlRequestDolibarr('POST', 'subscriptions', $cotisationGratuite);
        if($reponse['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de la cotisation : ".$cotisationGratuite['note']);
            return false;
        }

        //Cotisation au prorata pour le reste de l'année, sauf pour le mois de décembre.
        if((new \DateTime())->format('m') != '12'){
            $startNextMonth = (new \DateTime())->modify("first day of next month");
            $endOfYear = (new \DateTime())->modify("last day of this year");
            $numberOfDays = $endOfYear->diff($startNextMonth)->format("%a");
            $montant = round(($numberOfDays * $dossierAgrement->getMontant())/365,2);

            $cotisationProrata = $this->transformCotisation(
                $startNextMonth,
                $endOfYear,
                $montant,
                "Adhésion/cotisation".date('Y'),
                $dossierAgrement
            );
            $reponse = $this->curlRequestDolibarr('POST', 'subscriptions', $cotisationProrata);
            if($reponse['httpcode'] != 200) {
                dump($reponse['data']);
                $this->addFlash("danger","Erreur lors de la cotisation : ".$cotisationProrata['note']);
                return false;
            }
        }

        return true;
    }

    /**
     * Transforme un objet Document vers un tableau compatible document dolibarr
     *
     * @param DossierAgrement $dossierAgrement
     * @return array
     */
    private function transformCotisation(\DateTime $start, \DateTime $end, $montant, $label, DossierAgrement $dossierAgrement){

        $data =
            [
                "datec"=> (new \DateTime('now'))->getTimestamp(),
                "datem"=> $start->getTimestamp(),
                "dateh"=> $start->getTimestamp(),
                "datef"=> $end->getTimestamp(),
                "fk_adherent"=> $dossierAgrement->getIdAdherent(),
                "amount"=> $montant,
                "fk_bank"=> "55495",
                "note"=> $label,
            ];

        return $data;

    }

    /**
     * Transforme un objet Document vers un tableau compatible document dolibarr
     *
     * @param Document $document
     * @return array
     */
    private function transformDocument(Document $document){
        $file = file_get_contents($document->getAbsolutePath());

        $data =
            [
                "filename"=> $document->getPath(),
                "modulepart"=> "adherent",
                "ref"=> $document->getDossierAgrement()->getIdAdherent(),
                "subdir"=> "",
                "filecontent"=> base64_encode($file),
                "fileencoding"=> "base64",
                "overwriteifexists"=> 0
            ];

        return $data;

    }

    /**
     * Transforme un objet Defi vers un tableau compatible adresse dolibarr
     *
     * @param Defi $defi
     * @return array
     */
    private function transformDefi(Defi $defi){

        $etat = '0';
        if($defi->isEtat()){
            $etat = '-1';
        }
        $debut = (new \DateTime())->modify('first day of January this year 00:00')->getTimestamp();
        $fin = (new \DateTime())->modify('last day of December next year 00:00')->getTimestamp();
        $data =
            [
                "type_code"=> "AC_DEFI",
                'type' => "Défi",
                'code' =>  "AC_DEFI",
                "label"=> $defi->getLabelDefiCRM(),
                "datep"=> $debut,
                "datef"=> $fin,
                "percentage" => $etat,
                "userownerid"=> "2619",
                "socid"=> $defi->getDossierAgrement()->getIdExterne(),
                "note" => $defi->getValeur()
            ];

        return $data;

    }

    /**
     * Transforme un objet AdresseActivite vers un tableau compatible adresse dolibarr
     *
     * @param AdresseActivite $adresseActivite
     * @return array
     */
    private function transformAdresseActivite(AdresseActivite $adresseActivite){
        $adresse = json_decode($adresseActivite->getAdresse());
        $array_options = [
            "options_latitude"=> $adresse->lat,
            "options_longitude"=> $adresse->lng,
            "options_facebook"=> $adresseActivite->getFacebook(),
            "options_instagram"=> $adresseActivite->getInstagram(),
            "options_description_francais"=> $adresseActivite->getDescriptifActivite(),
            "options_horaires_francais"=> $adresseActivite->getHoraires(),
            "options_autres_lieux_activite_francais"=> $adresseActivite->getAutresLieux(),
            //"options_euskara"=> "3",
            //"options_horaires_euskara"=> "Astelehenetik ostiralera 8=>00 – 12=>00 / 14=>00 – 18=>30 (astelehenetan=> 9=>00etatik)",
            //"options_autres_lieux_activite_euskara"=> null,
            //"options_bons_plans_euskara"=> null,
            //"options_bons_plans_francais"=> null,
            //"options_equipement_pour_euskokart"=> "oui_famoco",
            //"options_euskopay"=> "1"
        ];

        $data =
            [
                'address' => $adresseActivite->getComplementAdresse()!=''?$adresseActivite->getComplementAdresse():$adresse->address,
                'zip' => $adresse->postcode,
                'town' => $adresse->id,
                "socid"=> $adresseActivite->getDossier()->getIdExterne(),
                "email"=> $adresseActivite->getEmail(),
                "mail"=> $adresseActivite->getEmail(),
                "phone_pro"=> $adresseActivite->getTelephone(),
                "lastname"=> $adresseActivite->getNom(),
                "socname"=> $adresseActivite->getNom(),
                'array_options' => $array_options
            ];

        return $data;
    }

    /**
     * Transforme un objet DossierAgrement vers un tableau compatible tier dolibarr
     *
     * @param DossierAgrement $dossierAgrement
     * @return array
     */
    private function transformTier(DossierAgrement $dossierAgrement){
        $adresse = json_decode($dossierAgrement->getAdressePrincipale());

        $array_options = [
            "options_date_agrement"=> $dossierAgrement->getDateAgrement()->getTimestamp(),
            "options_montant_frais_de_dossier"=> $dossierAgrement->getFraisDeDossier(),
            "options_prefere_etre_contacte"=> "Mail"
        ];

        $data =
            [
                'address' => $dossierAgrement->getComplementAdresse()!=''?$dossierAgrement->getComplementAdresse():$adresse->address,
                'zip' => $adresse->postcode,
                'town' => $adresse->id,
                "email"=> $dossierAgrement->getEmailPrincipal(),
                "phone_pro"=> $dossierAgrement->getTelephone(),
                "name_alias"=> $dossierAgrement->getFormeJuridique().' '.$dossierAgrement->getDenominationCommerciale(),
                "name"=> $dossierAgrement->getDenominationCommerciale(),
                "client"=> "1",
                "code_client"=> $dossierAgrement->getCodePrestataire(),
                'array_options' => $array_options
            ];

        return $data;
    }

    /**
     * Transforme un objet DossierAgrement vers un tableau compatible adherent dolibarr
     *
     * @param DossierAgrement $dossierAgrement
     * @return array
     */
    private function transformAdherent(DossierAgrement $dossierAgrement){
        $adresse = json_decode($dossierAgrement->getAdressePrincipale());

        //Parcours des réduction, si il y en a une c'est la valeur 1 (25%) dans dolibarr
        //Si une seule des réductions cochée est > 26 donc c'est la valeur 2 (50%) dans dolibarr
        $options_reduction_cotisation = '0';
        foreach ($dossierAgrement->getReductionsAdhesion() as $reduction){
            $options_reduction_cotisation = '1';
            if($reduction->getPourcentageReduction()>26){
                $options_reduction_cotisation = '2';
            }
        }

        $array_options = [
            "options_recevoir_actus"=> $dossierAgrement->isRecevoirNewsletter(),
            "options_iban"=> $dossierAgrement->getIban(),
            "options_bic"=> $dossierAgrement->getBic(),
            "options_prelevement_auto_cotisation"=> '1',
            //"options_prelevement_auto_cotisation_eusko"=> '1',
            "options_nb_salaries"=> $dossierAgrement->getNbSalarie(),
            "options_reduction_cotisation"=> $options_reduction_cotisation,
            "options_cotisation_soutien"=> ($dossierAgrement->getTypeCotisation() == 'solidaire')?'1':'0',
            "options_prelevement_cotisation_montant"=> $dossierAgrement->getMontant(),
            "options_prelevement_cotisation_periodicite"=> '12',
            "options_accepte_cgu_eusko_numerique" => '1',
            "options_documents_pour_ouverture_du_compte_valides" => '1',
            "options_accord_pour_ouverture_de_compte" => 'oui',
            "options_notifications_validation_mandat_prelevement"=> '1',
            "options_notifications_refus_ou_annulation_mandat_prelevement"=> '1',
            "options_notifications_prelevements"=> '1',
            "options_notifications_virements"=> '1',
            "options_recevoir_bons_plans"=> '1',
        ];
        $data =
            [
                "login"=> $dossierAgrement->getCodePrestataire(),
                "typeid"=> $dossierAgrement->getType() == 'entreprise'?"2":"1",
                "morphy"=> "mor",
                "lastname"=>  $dossierAgrement->getNomDirigeant(),
                "firstname"=>  $dossierAgrement->getPrenomDirigeant(),
                "email"=> $dossierAgrement->getEmailDirigeant(),
                'address' => $adresse->address,
                'zip' => $adresse->postcode,
                'town' => $adresse->id,
                "phone_mobile"=> $dossierAgrement->getTelephoneDirigeant(),
                "fk_soc"=> $dossierAgrement->getIdExterne(),
                "societe"=> $dossierAgrement->getDenominationCommerciale(),
                "company"=> $dossierAgrement->getDenominationCommerciale(),
                "public"=> "0",
                "statut"=> "1",
                'array_options' => $array_options
            ];

        return $data;
    }

    /**
     * Transforme un objet Contact vers un tableau compatible contact dolibarr
     *
     * @param Contact $contact
     * @return array
     */
    private function transformContact(Contact $contact){

        $data =
            [
                "socid"=> $contact->getDossierAgrement()->getIdExterne(),
                "email"=> $contact->getEmail(),
                "mail"=> $contact->getEmail(),
                "phone_pro"=> $contact->getTelephone(),
                "lastname"=> $contact->getNom(),
                "firstname"=> $contact->getPrenom(),
                "poste"=> $contact->getFonction(),
                "socname"=> $contact->getDossierAgrement()->getDenominationCommerciale()
            ];

        return $data;
    }

}
