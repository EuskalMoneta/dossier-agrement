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

    private $base_url;
    private $api_token_dolibarr;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->base_url = $_ENV['API_DOLIBARR_URL'];
        $this->api_token_dolibarr = $_ENV['API_TOKEN_DOLIBARR'];
        $this->logger = $logger;
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
        curl_setopt($curl, CURLOPT_URL, $this->base_url.$link);
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


    public function getCategoriesEskuzEsku():array
    {
        $tabCategoriesEskuzEsku = [];
        $this->getCategoriesChildren('376', $tabCategoriesEskuzEsku);
        return $tabCategoriesEskuzEsku;
    }

    public function searchProfessionnel($term):array
    {
        $tabPro = [];

        $url = "thirdparties?limit=10&sqlfilters=".urlencode("(t.status:=:1) and (t.nom:like:'%".$term."%')");
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

    /*
     * Fonction récursive qui récupère l'ensemble des sous catégories d'un index donné.
     * Prend l'index et un tableau par référence pour les résultats
     */
    private function getCategoriesChildren($id_parent, &$tabCategories): bool
    {

        $reponseCategories = $this->curlRequestDolibarr('GET', 'categories?limit=200&type=contact&sqlfilters=(t.fk_parent:=:'.$id_parent.')');
        if($reponseCategories['httpcode'] == 200) {
            if(count($reponseCategories['data']) == 0){
                return true;
            } else {
                foreach ($reponseCategories['data'] as $categorie){
                    $tabCategories[] = [
                        'idExterne' => $categorie->id,
                        'libelle' => explode('/', $categorie->label)[1]
                    ];
                    $this->getCategoriesChildren($categorie->id, $tabCategories);
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
            }
            return $reponseTier['data']->id;
        } else {
            //sinon on ajoute un nouveau tier
            $reponseTier = $this->curlRequestDolibarr('POST', 'thirdparties', $data);
            if($reponseTier['httpcode'] != 200) {
                $this->addFlash("danger", "Erreur lors de l'ajout du tier dans dolibarr.");
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
        }

        return true;
    }

    public function postDocument(Document $document){
        $data = $this->transformDocument($document);
        $reponse = $this->curlRequestDolibarr('POST', 'agendaevents', $data);
        if($reponse['httpcode'] != 200) {
            dump($reponse);
            $this->addFlash("danger","Erreur lors de l'ajout du document : ".$document->getPath());
        }

        return true;

    }

    /**
     * Transforme un objet Document vers un tableau compatible document dolibarr
     *
     * @param Defi $defi
     * @return array
     */
    private function transformDocument(Document $document){
        $file = file_get_contents($document->getAbsolutePath());

        $data =
            [
                "filename"=> $document->getPath(),
                "modulepart"=> "tier",
                "ref"=> $document->getDossierAgrement()->getIdExterne(),
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
                'address' => $adresse->address,
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
                'address' => $adresse->address,
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
