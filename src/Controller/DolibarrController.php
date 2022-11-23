<?php

namespace App\Controller;

use App\Entity\DossierAgrement;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
                        'adresse' => '',
                        'commentaires' => $pro->note_private,
                        'status' => $status,
                    ];
            }
        }
        return $tabPro;
    }

    /*
     * Fonction récursive qui récupère l'ensemble des sous catégories d'un index donné.
     * Prend l'index et un tableau par référence pour les résultats
     */
    private function getCategoriesChildren($id_parent, &$tabCategories){

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
    }

    public function updateTier($id): bool
    {
        return true;
    }

}
