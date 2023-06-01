<?php

namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\Contact;
use App\Entity\Defi;
use App\Entity\Document;
use App\Entity\DossierAgrement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Ripcord\Ripcord;


class OdooController extends AbstractController implements CRMInterface
{

    private $odoo_url;
    private $api_token_odoo;
    private $logger;

    private $odoo_user;
    private $odoo_pass;

    private $odoo_db_name;

    private $cyclos_url;
    private $cyclos_user;
    private $cyclos_pass;

    public function __construct(LoggerInterface $logger)
    {
        $this->odoo_url = $_ENV['API_ODOO_URL'];

        $this->odoo_user = $_ENV['API_ODOO_USER'];
        $this->odoo_pass = $_ENV['API_ODOO_PASS'];
        $this->odoo_db_name = $_ENV['API_ODOO_DB_NAME'];

        $this->cyclos_url = $_ENV['API_CYCLOS_URL'];
        $this->cyclos_user = $_ENV['API_CYCLOS_USER'];
        $this->cyclos_pass = $_ENV['API_CYCLOS_PASS'];

        $this->logger = $logger;

        $this->api_token_odoo = $this->loginRequestOdoo();
    }

    private function loginRequestOdoo()
    {
        $common = ripcord::client("$this->odoo_url/xmlrpc/2/common");
        $uid = $common->authenticate($this->odoo_db_name, $this->odoo_user, $this->odoo_pass, array());
        if(!is_bool($uid)) {
            return $uid;
        } else {
            $this->addFlash("danger","Erreur lors de la connexion à Odoo");
            return false;
        }
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

        if($this->api_token_odoo) {
            $token = $this->api_token_odoo;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->odoo_url.$link);
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
                    'DOLAPIKEY: ' . $token)
            );
        }

        $return = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return ['data' => json_decode($return), 'httpcode' => $http_status];
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

    public function postBankUser(DossierAgrement $dossierAgrement):int
    {
        //On vérifie si aucun utilisateur avec ce login existe.
        $responseExist = $this->curlRequestCyclos('POST', '/user/search', [
            'keywords'=> $dossierAgrement->getCodePrestataire(),
            ['userStatus'=> ['ACTIVE', 'BLOCKED', 'DISABLED']]
        ]);
        if ($responseExist['httpcode'] != 200) {
            $this->addFlash('warning', "Erreur lors de la recherche de l'utilisateur dans Cyclos.");
        } else if ($responseExist['data']->result->totalCount > 0) {
            $this->addFlash('warning', "Utilisateur déjà présent dans Cyclos, le compte cyclos n'a pas été créé.");
        } else {
            //Préparation et enregistrement de l'utilisateur
            $group = $_ENV['ADHERENTS_SANS_COMPTE'];
            if ($dossierAgrement->isCompteNumeriqueBool()) {
                $group = $_ENV['ADHERENTS_PRESTATAIRES'];
                if ($dossierAgrement->isPaiementViaEuskopay()) {
                    $group = $_ENV['ADHERENTS_PRESTATAIRES_AVEC_PAIEMENT_SMARTPHONE'];
                }
            }

            $data = [
                'group'=> $group,
                'name'=> $dossierAgrement->getDenominationCommerciale(),
                'username'=> $dossierAgrement->getCodePrestataire(),
                'skipActivationEmail'=> True,
            ];
            $responseUser = $this->curlRequestCyclos('POST', '/user/register', $data);
            if ($responseUser['httpcode'] != 200) {
                $this->addFlash('danger', "L'utilisateur Cyclos n'a pas été créé.");
            } else {
                $this->addFlash('success', "Utilisateur Cyclos ajouté avec succès.");

                //si l'utilisateur a un compte numérique, changer son status
                if ($dossierAgrement->isCompteNumeriqueBool()) {
                    $data = [
                        'user'=> $responseUser['data']->result->user->id,
                        'status'=> 'ACTIVE',
                    ];
                    $responseActivation = $this->curlRequestCyclos('POST', '/userStatus/changeStatus', $data);
                    if($responseActivation['httpcode'] != 200) {
                        $this->addFlash('danger', "L'utilisateur Cyclos n'a pas pu être activé.");
                    } else {
                        // et créer un QR code pour cet utilisateur
                        $data = [
                            'user'=> $responseUser['data']->result->user->id,
                            'type'=> 'qr_code',
                            'value'=> $dossierAgrement->getCodePrestataire(),
                        ];
                        $responseQrCode = $this->curlRequestCyclos('POST', '/token/save', $data);
                        if ($responseQrCode['httpcode'] != 200) {
                            $this->addFlash('danger', "Le QR code n'a pas pu être créé.");
                        } else {
                            $responseActivationQrCode = $this->curlRequestCyclos('POST', '/token/activatePending', [$responseQrCode['data']->result]);
                            if($responseActivationQrCode['httpcode'] != 200) {
                                $this->addFlash('danger', "Le QR code n'a pas pu être activé.");
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function getCategoriesAnnuaire():array
    {
        $tabCategoriesAnnuaire =[];
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $res = array();
        $id_categorieAnnuaire['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner.industry','search_read',
            array(array(
                array('name', '=', 'Annuaire général'),
            )),
            array('fields'=>array('id','name')));
        $id_categorieAnnuaire= array_column($id_categorieAnnuaire['data'], 'id');
        $test = $id_categorieAnnuaire[0];
        $search_string = "'" .strval($test) ."/%'";
        $search_string = preg_match("/([^']+)/", $search_string, $matches);
        $domain = array(array(
            array('parent_path', 'like',  $matches[0]),
            array('parent_id', '!=',  false),
            array('child_ids', '=',  false)
        ));
        $fields = array('fields'=>array('id', 'name', 'parent_path', 'parent_id'));
        $tabCategoriesAnnuaire ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass ,
            'res.partner.industry','search_read', $domain, $fields);
        foreach ($tabCategoriesAnnuaire['data'] as $categorie) {
            $domain = array(array(
                array('src', '=', $categorie['name']),
                array('lang', '=',  'fr_FR'),
                array('state', '=',  'translated')
            ));
            $result = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'ir.translation', 'search_read', $domain, array('fields'=>array('value')));
            $res[]= [
                'idExterne' => $categorie['id'],
                'libelle' => $categorie['parent_id'][1] . ' > ' .$result[0]['value']
            ];
        }
        return $res;
    }

    public function searchActiveUser(string $email):int
    {
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $domain = array(array(array('active', '=', true),array('email', 'like', $email)));
        $responseUser['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res_users', 'search_read', $domain, array('fields'=>array('id')));
        foreach ($responseUser['data'] as $user){
                return $user['id'];
            }
        return 0;
    }

    public function searchProfessionnel($term):array
    {
        $tabPro = [];
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $result ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass ,
            'member.type', 'search_read',
            array(array(
            array('name', '!=', 'Particulier'),
            array('name', '!=', 'Touriste'),
            array('name', '!=', 'Adhérent fictif'))),
            array('fields' => array('id', 'name')));
        $id_member_type = array_column($result['data'], 'id');
        $profils_principaux['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass ,
            'res.partner','search_read',
            array(array(
                array('membership_state', 'not in', ['none', 'canceled']),
                array('member_type_id','in',$id_member_type),
                array('is_main_profile', '=', True),
                array('associate_member','=',False),
                array('name', 'like', $term),
                )),
            array('fields'=>array('name', 'website_description', 'street', 'city','zip', 'phone', 'secondary_industry_ids','email')));
        if (!empty($profils_principaux))
        {
            foreach ($profils_principaux['data'] as $pro){

                $adresse = $this->transformAdresseIntoJSON(
                    $pro['street'].' '.$pro['zip'].' '.$pro['city'],
                    $pro['city'],
                    '',
                    '',
                    $pro['street'],
                    $pro['zip'],
                    ''
                );
                $tabPro[] = [
                    'id' => 'CRM'.$pro['id'],
                    'text' => $pro['name'],
                    'prenom' => '',
                    'nom' => '',
                    'entreprise' => $pro['name'],
                    'activite' => '',
                    'telephone' => $pro['phone'],
                    'email' =>  $pro['email'],
                    'adresse' => $adresse,
                    'commentaires' => '',
                    'status' => "Prestataire agréé",
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
        $tabCategoriesEskuzEsku =[];
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $res = array();
        $domain =array(array(array('name', '=', 'Eskuz Esku')));
        $id_EskuzEsku['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass ,
            'res.partner.industry','search_read',
            array(array(
                array('name', '=', 'Eskuz Esku'),
            )),
            array('fields'=>array('id','name')));
        $id_EskuzEsku = array_column($id_EskuzEsku['data'], 'id');
        $test = $id_EskuzEsku[0];
        $search_string = "'" .strval($test) ."/%'";
        $search_string = preg_match("/([^']+)/", $search_string, $matches);
        $domain =array(array(
            array('parent_path', 'like',  $matches[0]),
            array('parent_id', '!=',  false),
            array('child_ids', '=',  false)
        ));
        $fields = array('fields'=>array('id', 'name', 'parent_path', 'parent_id'));
        $tabCategoriesEskuzEsku ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass ,
            'res.partner.industry','search_read', $domain, $fields);
        /*$tabCategoriesEskuzEsku = $this->RequestOdoo('search_read','res.partner.industry',$domain,$fields);*/
        foreach ($tabCategoriesEskuzEsku['data'] as $categorie) {
            $domain = array(array(
                array('src', '=', $categorie['name']),
                array('lang', '=',  'fr_FR'),
                array('state', '=',  'translated')
            ));
            $result = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'ir.translation', 'search_read', $domain, array('fields'=>array('value')));
            $res[]= [
                'idExterne' => $categorie['id'],
                'libelle' => $categorie['parent_id'][1] . ' > ' .$result[0]['value']
            ];
        }
        return $res;
    }
    /*
     * Fonction récursive qui récupère l'ensemble des sous catégories d'un index donné.
     * Prend l'index et un tableau par référence pour les résultats
     */
    public function updateTier($id): bool
    {
        return true;
    }


    public function postAdresseActivite(AdresseActivite $adresseActivite): bool
    {

        $data = $this->transformAdresseActivite($adresseActivite);
        $reponseAdresseActivite = $this->curlRequestDolibarr('POST', 'contacts', $data);
        if ($reponseAdresseActivite['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout de l'adresse d'activité : ".$adresseActivite->getNom());
            return false;
        }
        $idAdresseActivite = $reponseAdresseActivite['data'];

        // Ajouter l'étiquette "Adresse d'activité"
        $reponseLiaison = $this->curlRequestDolibarr('POST', 'categories/370/objects/contact/'.$idAdresseActivite);
        if ($reponseLiaison['httpcode'] != 200) {
            $this->addFlash("danger", "Erreur lors de l'ajout de l'étiquette \"Adresse d'activité\" à l'adresse d'activité : ".$adresseActivite->getNom());
            return false;
        }

        // Ajouter les étiquettes des catégories des annuaires
        $categories = array_merge($adresseActivite->getCategoriesAnnuaire()->toArray(), $adresseActivite->getCategoriesAnnuaireEskuz()->toArray());
        foreach ($categories as $cat) {
            $reponseLiaison = $this->curlRequestDolibarr('POST', 'categories/'.$cat->getIdExterne().'/objects/contact/'.$idAdresseActivite);
            if ($reponseLiaison['httpcode'] != 200) {
                $this->addFlash("danger", "Erreur lors de l'ajout de la catégorie '.$cat->getIdExterne().' à l'adresse d'activité : ".$adresseActivite->getNom());
                return false;
            }
        }

        // Ajouter l'étiquette "Vacances en eusko"
        if ($adresseActivite->isGuideVEE()) {
            $reponseLiaison = $this->curlRequestDolibarr('POST', 'categories/489/objects/contact/'.$idAdresseActivite);
            if ($reponseLiaison['httpcode'] != 200) {
                $this->addFlash("danger", "Erreur lors de l'ajout de l'étiquette \"Vacances en eusko\" à l'adresse d'activité : ".$adresseActivite->getNom());
                return false;
            }
        }

        return true;
    }

    public function postContact(Contact $contact): int
    {
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");

        $data = $this->transformContact($contact);

        $reponse ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass, 'res.partner', 'create', array($data));
        var_dump($reponse ['data']);
        $test = $this->transformPostion($contact,$reponse ['data']);
        $postion = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass, 'res.partner', 'create', array($test));
        return true;
        //adresse activité / multi établissement



        /*$reponseCategories = $this->curlRequestDolibarr('POST', 'contacts', $data);
        if($reponseCategories['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout du contact : ".$contact->getNom());
            return false;
        }
        return true;*/
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
                $this->logger->error("Erreur lors de la mise à jour du tiers dans Dolibarr : ".$reponseTier['data']->error->message);
                return false;
            }
            return $reponseTier['data']->id;
        } else {
            //sinon on ajoute un nouveau tier
            $reponseTier = $this->curlRequestDolibarr('POST', 'thirdparties', $data);
            if($reponseTier['httpcode'] != 200) {
                $this->addFlash("danger", "Erreur lors de l'ajout du tier dans dolibarr.");
                $this->logger->error("Erreur lors de l'ajout du tiers dans Dolibarr : ".$reponseTier['data']->error->message);
                return false;
            }
            return $reponseTier['data'];
        }

    }

    public function postDefi(Defi $defi): int
    {
        $idUser = $this->searchActiveUser($defi->getDossierAgrement()->getUtilisateur()->getEmail());

        if($idUser > 0){
            $data = $this->transformDefi($defi, $idUser);
            $reponseCategories = $this->curlRequestDolibarr('POST', 'agendaevents', $data);
            if($reponseCategories['httpcode'] != 200) {
                $this->addFlash("danger","Erreur lors de l'ajout du defi : ".$defi->getValeur());
                return false;
            }
        } else {
            $this->addFlash("danger","Erreur lors de l'ajout du defi : utilisateur non trouvé");
            return false;
        }

        return true;
    }

    public function postDocument(Document $document):int
    {
        $data = $this->transformDocument($document);
        $reponse = $this->curlRequestDolibarr('POST', 'documents/upload', $data);

        if($reponse['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de l'ajout du document : ".$document->getPath());
            $this->logger->error("Erreur lors de l'ajout du document : ".$document->getPath()."\nErreur : ".$reponse['data']->error->message);
            return false;
        }

        return true;
    }

    public function postAdherent(DossierAgrement $dossierAgrement):int
    {

        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $data = $this->transformAdherent($dossierAgrement);

        var_dump($dossierAgrement->getIdExterne());
        if ($dossierAgrement->getIdExterne() > 0) {
            //Si le tier existe déjà, on fait une mise à jour
            /*$reponseTier = $this->curlRequestDolibarr('PUT', 'thirdparties/'.$dossierAgrement->getIdExterne(), $data);*/
            $dat = $this->transformTier($dossierAgrement);
            $reponse= $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass, 'res.partner', 'write', array(array($dossierAgrement->getIdExterne()), $dat));
            return true;
        } else {
            //sinon on ajoute un nouveau tier
            $reponse ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass, 'res.partner', 'create', array($data));
            if (!is_int($reponse ['data'])) {
                $this->addFlash("danger", "Erreur lors de l'ajout de l'adhérent : " . $dossierAgrement->getCodePrestataire());
                $this->logger->error("Erreur lors de l'ajout de l'adhérent dans Dolibarr : " . $reponse['data']->error->message);
                return false;
            }
            return $reponse['data'];
        }
    }
        /*if($dossierAgrement->getIdAdherent() > 0){
            $this->addFlash("warning","L'adhérent existe déjà, impossible d'en créer un nouveau");
        } else {
            $reponse ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass, 'res.partner', 'create', array($data));

            if(!is_int($reponse ['data'])) {
                $this->addFlash("danger","Erreur lors de l'ajout de l'adhérent : ".$dossierAgrement->getCodePrestataire());
                $this->logger->error("Erreur lors de l'ajout de l'adhérent dans Dolibarr : ".$reponse['data']->error->message);

            }
            return $reponse['data'];
        }

    }*/

    public function postCotisation(DossierAgrement $dossierAgrement): int{

        // La cotisation est offerte pour le premier mois.
        $cotisation = $this->transformCotisation(
            $dossierAgrement->getDateAgrement(),
            (new \DateTime())->modify("last day of this month"),
            0,
            "Adhésion/cotisation ".date('Y')
        );
        $reponse = $this->curlRequestDolibarr('POST', 'members/'.$dossierAgrement->getIdAdherent().'/subscriptions', $cotisation);
        if ($reponse['httpcode'] != 200) {
            $this->addFlash("danger","Erreur lors de la création de la cotisation.");
            $this->logger->error("Erreur lors de la création de la cotisation dans Dolibarr : ".$reponse['data']->error->message);
            return false;
        }

        return true;
    }

    /**
     * Transforme un objet Document vers un tableau compatible document dolibarr
     *
     * @param DossierAgrement $dossierAgrement
     * @return array
     */
    private function transformCotisation(\DateTime $start, \DateTime $end, $montant, $label){

        $data =
            [
                "start_date"=> $start->getTimestamp(),
                "end_date"=> $end->getTimestamp(),
                "amount"=> $montant,
                "label"=> $label,
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

        //$document->getPath()
        $data =
            [
                "filename"=> $document->getFileNameFromType(),
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
     * @param int $idUser
     * @return array
     */
    private function transformDefi(Defi $defi,int $idUser){

        $etat = '0';
        $debut = (new \DateTime())->modify('first day of January this year 00:00')->getTimestamp();
        $fin = (new \DateTime())->modify('last day of December next year 00:00')->getTimestamp();

        if($defi->isEtat()){
            $etat = '-1';
            $fin = (new \DateTime())->modify('last day of December this year 00:00')->getTimestamp();
        }

        $data =
            [
                "type_code"=> "AC_DEFI",
                'type' => "Défi",
                'code' =>  "AC_DEFI",
                "label"=> $defi->getLabelDefiCRM(),
                "datep"=> $debut,
                "datef"=> $fin,
                "percentage" => $etat,
                "userownerid"=> $idUser,
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
            "options_autres_lieux_activite_francais"=> $adresseActivite->getAutresLieux()
            //"options_horaires_euskara"=> "Astelehenetik ostiralera 8=>00 – 12=>00 / 14=>00 – 18=>30 (astelehenetan=> 9=>00etatik)",
            //"options_autres_lieux_activite_euskara"=> null,
            //"options_bons_plans_euskara"=> null,
            //"options_bons_plans_francais"=> null,
            //"options_equipement_pour_euskokart"=> "oui_famoco",
            //"options_euskopay"=> "1"
        ];
        if($adresseActivite->getDossier()->getTypeAutocollant()=='Bilingue/euskaraz'){
            $array_options["options_euskara"] = "3";
        } elseif ($adresseActivite->getDossier()->getTypeAutocollant()=='Premiers mots en langue basque/lehen hitza euskaraz'){
            $array_options["options_euskara"] = "1";
        }

        $data =
            [
                'address' => $adresseActivite->getAdresseComplete(),
                'zip' => $adresse->postcode,
                'town' => $adresse->id,
                'country_id' => 1,
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
            "options_montant_frais_de_dossier"=> $dossierAgrement->getFraisDeDossier()
        ];

        $data =
            [
                "street" => $dossierAgrement->getAdresseComplete(),
                "zip" => $adresse->postcode,
                "city" => $adresse->id,
                "country_id" => 5,
                "website" => $dossierAgrement->getSiteWeb(),
                "email"=> $dossierAgrement->isCompteNumeriqueBool() ? $dossierAgrement->getCompteNumerique() : $dossierAgrement->getEmailPrincipal(),
                "phone"=> $dossierAgrement->getTelephone(),
                "name"=> $dossierAgrement->getLibelle(),
                "ref"=> $dossierAgrement->getCodePrestataire(),
                "comment"=> $dossierAgrement->getNote()
                /*'array_options' => $array_options*/
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

        //Parcours des réduction, si il y en a au moins une => c'est la valeur 1 (25%) dans dolibarr
        //Si une seule des réductions cochée est > 26 donc c'est la valeur 2 (50%) qui l'emporte dans dolibarr
        $options_reduction_cotisation = '0';
        foreach ($dossierAgrement->getReductionsAdhesion() as $reduction){
            $options_reduction_cotisation = '1';
            if($reduction->getPourcentageReduction()>26){
                $options_reduction_cotisation = '2';
            }
        }

        $data =
            [
                "ref"=> $dossierAgrement->getCodePrestataire(),
                "member_type_id"=> $dossierAgrement->getType() == 'entreprise'?"2":"1",
                "morphy"=> "mor",//todo
                "email"=> $dossierAgrement->getCompteNumerique(),
                "street" => $adresse->postcode,
                "zip" => $adresse->postcode,
                "city" => $adresse->id,
                "country_id" => 1,
                "phone"=> $dossierAgrement->getTelephone(),
                /*"fk_soc"=> $dossierAgrement->getIdExterne(),//todo*/
                "name"=> $dossierAgrement->getLibelle(),
                "is_company"=>true,
                /*"company"=> $dossierAgrement->getLibelle(),//todo*/
                /*"public"=> "0",//todo*/
                "membership_state"=> "waiting",//todo
                "receive_actus"=> $dossierAgrement->isRecevoirNewsletter(),
                "iban"=> $dossierAgrement->getIban(),
                "bic"=> $dossierAgrement->getBic(),
                "direct_debit_auto_contribution"=> '1',
                //"options_prelevement_auto_cotisation_eusko"=> '1',
                "nb_salaried"=> (string) $dossierAgrement->getNbSalarie(),
                "discount_contribution"=> $options_reduction_cotisation,
                "support_contribution"=> ($dossierAgrement->getTypeCotisation() == 'solidaire')?'1':'0',
                "direct_debit_contribution_amount"=> $dossierAgrement->getMontant(),
                "direct_debit_contribution_periodicity"=> '12',
                "numeric_wallet_document_valid" => '1',
                "options_accord_pour_ouverture_de_compte" => 'oui',
                "notification_validation_mandat_prelevement"=> '1',
                "notification_refusal_or_cancellation_direct_debit"=> '1',
                "direct_debit_execution_notifications"=> '1',
                "transfer_receipt_notification"=> '1',
                "receive_good_plans"=> '1',

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
                "lastname"=> $contact->getNom(),
                "firstname"=> $contact->getPrenom(),
                "phone"=> $contact->getTelephone(),
                "email"=> $contact->getEmail()
            ];


        /*$data =
            [

                "socid"=> $contact->getDossierAgrement()->getIdExterne(),
                "email"=> $contact->getEmail(),
                "mail"=> $contact->getEmail(),
                "civility_id"=> $contact->getCivilite(),
                "phone_pro"=> $contact->getTelephone(),
                "lastname"=> $contact->getNom(),
                "firstname"=> $contact->getPrenom(),
                "poste"=> $contact->getFonction(),
                "socname"=> $contact->getDossierAgrement()->getDenominationCommerciale()
            ];*/

        return $data;
    }

    private function transformPostion(Contact $contact,int $id)
    {
        $data =
            [
                "name"=>$contact->getDossierAgrement()->getLibelle(),
                "contact_id"=> $id,
                "parent_id"=>$contact->getDossierAgrement()->getIdExterne(),
                "is_position_profile"=>true,
                "function"=>$contact->getFonction()
            ];
        return $data;
    }


}
