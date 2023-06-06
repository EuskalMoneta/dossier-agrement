<?php
namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\Contact;
use App\Entity\Defi;
use App\Entity\Document;
use App\Entity\DossierAgrement;

interface CRMInterface
{

    /**
     * Récupère un tableau des catégorie de l'annuaire
     * [
     *   ['idExterne' => $val,
     *    'libelle' => $val
     *   ],
     *   ['idExterne' => $val,
     *    'libelle' => $val
     *   ],
     *   ...
     * ]
     *
     * @return array
     */
    public function getCategoriesAnnuaire():array;

    /**
     * Récupère un tableau des catégorie de l'annuaire Eskuz esku
     * [
     *   ['idExterne' => $val,
     *    'libelle' => $val
     *   ],
     *   ['idExterne' => $val,
     *    'libelle' => $val
     *   ],
     *   ...
     * ]
     *
     * @return array
     */
    public function getCategoriesEskuzEsku():array;

    /**
     * Rechercher un professionnel
     * retourne un tableau de résultats
     * entrée : le nom du pro
     * retourne:
     * $tabPro[] = [
     *'id' => $val,
     *'text' => $val,
     *'prenom' => $val,
     *'nom' => $val,
     *'entreprise' => $val,
     *'activite' => $val,
     *'telephone' => $val,
     *'email' =>  $val,
     *'adresse' => $val,
     *'commentaires' => $val,
     *'status' => $val,
     *];
     *
     *
     *
     *
     * @param string $term
     * @return array
     */
    public function searchProfessionnel(string $term):array;

    public function postDocument(Document $document): int;
    public function postCotisation(DossierAgrement $dossierAgrement):int;
    /**
     * Ajoute une adresse d'activité liée à un tier
     *
     * @param AdresseActivite $adresseActivite
     * @return bool
     */
    public function postAdresseActivite(AdresseActivite $adresseActivite): bool;


    /**
     * Ajoute un nouveau contact
     *
     * @param Contact $contact
     * @return int
     */
    public function postContact(Contact $contact): int;

    /**
     * Ajoute un nouvel adhérent
     *
     * @param DossierAgrement $dossierAgrement
     * @return int Le numéro d'adhérent crée
     */
    public function postAdherent(DossierAgrement $dossierAgrement): int;


}
