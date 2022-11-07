<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class APITollboxController extends AbstractController
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
    public function curlRequestDolibarr($method, $link,  $data = '', $token ='')
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
}
