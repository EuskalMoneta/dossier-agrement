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
     * Makes a cUrl request for cyclos
     *
     * @param $method
     * @param $link
     * @param string $data
     * @return array
     */
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

    public function postAdresseActivite(AdresseActivite $adresseActivite): bool
    {
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $data = $this->transformAdresseActivite($adresseActivite);
        //creer l'adresse activité
        $reponse ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'create', array($data));
        //creer fiche position
        $fichposition = $this->transformPostionAdress($adresseActivite,$reponse ['data']);
        $resPos ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'create', array($fichposition));

        /*$partner = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'search_read', $domain, array('fields'=>array('id','secondary_industry_ids')));
        var_dump($partner);
        $r = [
            'name' => 'Collec',
            'secondary_industry_ids' => [207],
        ];
        $repons= $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'write', array(array($partner[0]['id']), $r));
        var_dump($repons);
        $si= $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'write', array(array($adresseActivite->getDossier()->getIdExterne()), $r));
        var_dump($si);*/

        // Ajouter les étiquettes des catégories des annuaires
       /*$categories = array_merge($adresseActivite->getCategoriesAnnuaire()->toArray(), $adresseActivite->getCategoriesAnnuaireEskuz()->toArray());

       var_dump($categories);*/

        /*$test = ["secondary_industry_ids"=> 215];

        $res = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'write', array(array(["id"=> 324]),$test));
        var_dump($res);*/
        /*foreach ($categories as $cat) {

            $test = ["secondary_industry_ids"=> 215];
            $res = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'res.partner', 'write', array(array($adresseActivite->getDossier()->getIdExterne()),$test));
            var_dump($res);
        }*/
        return true;
    }

    public function postContact(Contact $contact): int
    {
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $data = $this->transformContact($contact);
        $domain = array(array(
            array('email', '=',$contact->getEmail())
        ));
        $fields = array('fields'=>array('id', 'name'));
        $r['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'res.partner', 'search_read', $domain, array('fields'=>array('id')));
        if ($r['data'] == [])
        {
            //Creation du partner (contact)
            $reponse ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'res.partner', 'create', array($data));
            ////Creation de la position
            $fichposition = $this->transformPostion($contact,$reponse ['data']);
            $resPos ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'res.partner', 'create', array($fichposition));
            //email unique attention
        }
        return false;
    }
    public function postDocument(Document $document): int
    {
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $data = $this->transformDocument($document);
        $resPos ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'ir.attachment', 'create', array($data));
        var_dump($resPos ['data']);
        return true;
    }
    public function postCotisation(DossierAgrement $dossierAgrement): int{

        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        // La cotisation est offerte pour le premier mois.
        /*\$dossierAgrement->getDateAgrement()*/
        $date = date_create("2023-01-01");
        $cotisation = $this->transformCotisation(
            $date,
            (new \DateTime())->modify("last day of this month"),
            0,
            "Adhésion/cotisation ".date('Y')
        );
        $reponse ['data']= $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
            'membership.membership_line', 'create', array($cotisation));
        var_dump($reponse ['data']);

        return true;
    }

    public function postAdherent(DossierAgrement $dossierAgrement):int
    {
        $models = ripcord::client("$this->odoo_url/xmlrpc/2/object");
        $data = $this->transformAdherent($dossierAgrement);
        if ($dossierAgrement->getIdExterne() > 0) {
            //Si le partner existe déjà, on fait une mise à jour
            $adh= $this->transformTier($dossierAgrement);
            //update du pro
            $reponse= $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'res.partner', 'write', array(array($dossierAgrement->getIdExterne()), $adh));
            return $dossierAgrement->getIdExterne();
        } else {
            //sinon on ajoute un nouveau tier
            $reponse ['data'] = $models->execute_kw($this->odoo_db_name, $this->api_token_odoo, $this->odoo_pass,
                'res.partner', 'create', array($data));
            if (!is_int($reponse ['data'])) {
                $this->addFlash("danger", "Erreur lors de l'ajout de l'adhérent : " . $dossierAgrement->getCodePrestataire());
                $this->logger->error("Erreur lors de l'ajout de l'adhérent dans Dolibarr : " . $reponse['data']->error->message);
                return false;
            }
            return $reponse['data'];
        }
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
            "partner_latitude"=> $adresse->lat,
            "partner_longitude"=> $adresse->lng,
            "options_facebook"=> $adresseActivite->getFacebook(),//TODO
            "options_instagram"=> $adresseActivite->getInstagram(),//TODO
            "options_description_francais"=> $adresseActivite->getDescriptifActivite(),//TODO
            "opening_time"=> $adresseActivite->getHoraires(),
            "options_autres_lieux_activite_francais"=> $adresseActivite->getAutresLieux()//TODO
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
                "street" => $adresseActivite->getAdresseComplete(),
                "zip" => $adresse->postcode,
                "city" => $adresse->id,
                "country_id" => 1,
                "is_company"=>true,
                "phone"=> $adresseActivite->getTelephone(),
                "name"=> $adresseActivite->getNom(),
                /*'array_options' => $array_options*/
            ];

        return $data;
    }
    private function transformDocument(Document $document)
    {
        $file = file_get_contents($document->getAbsolutePath());
        $data = [
            "datas_fname" =>$document->getFileNameFromType(),
            "res_model_name" => "Contact",
            "res_id" => $document->getDossierAgrement()->getIdExterne(),
            "res_model" => "res.partner",
            "res_name" => $document->getDossierAgrement()->getLibelle(),
            "datas"=> base64_encode($file),
            "store_fname" =>$document->getFileNameFromType(),
            "name" =>$document->getFileNameFromType(),
        ];
        return $data;
    }

    private function transformCotisation(\DateTime $start, \DateTime $end, $montant, $label)
    {
        $data =[
            "date" =>date_format($start, 'Y/m/d'),
            "date_to" =>date_format($end, 'Y/m/d'),
            "date_from"=>date_format($start, 'Y/m/d'),
            "partner"=>324,
            "member_price"=>$montant,
            "membership_id"=>1,
        ];
        return $data;
    }



    /**
     * Transforme un objet DossierAgrement vers un tableau compatible partner Odoo
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
     * Transforme un objet DossierAgrement vers un tableau compatible partner Odoo
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
                "email"=> $dossierAgrement->getEmailPrincipal(),
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
     * Transforme un objet Contact vers un tableau compatible partner Odoo
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
        return $data;
    }

    /**
     * transformation tableau vers un tableau pour une fiche position entre un contat (personne physique) et un partner Odoo
     *
     * @param Contact $contact
     * @param int $id
     * @return array
     */
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

    /**
     * transformation tableau vers un tableau pour une fiche position entre un contat (adresse d'un site) et un partner Odoo (Du siège)
     *
     * @param AdresseActivite $adresseActivite
     * @param int $id
     * @return array
     */
    private function transformPostionAdress(AdresseActivite $adresseActivite,int $id)
    {
        $data =
            [
                "name"=>$adresseActivite->getDossier()->getLibelle(),
                "contact_id"=> $id,
                "parent_id"=>$adresseActivite->getDossier()->getIdExterne(),
                "is_position_profile"=>true,
                /*"function"=>$adresseActivite->getFonction()*/
            ];
        return $data;
    }



}
