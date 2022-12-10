<?php
namespace App\Controller;

use App\Entity\AdresseActivite;
use App\Entity\Contact;
use App\Entity\Defi;
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
     *
     * @param string $term
     * @return array
     */
    public function searchProfessionnel(string $term):array;

    /**
     * Mettre à jour un tier existant
     *
     * @param int $id
     * @return bool
     */
    public function updateTier(int $id):bool;

    /**
     * Ajoute une adresse d'activité liée à un tier
     *
     * @param AdresseActivite $adresseActivite
     * @return bool
     */
    public function postAdresseActivite(AdresseActivite $adresseActivite): bool;


    /**
     * Ajoute un nouveau tier
     *
     * @param DossierAgrement $dossierAgrement
     * @return int
     */
    public function postTier(DossierAgrement $dossierAgrement): int;

    /**
     * Ajoute un nouveau contact
     *
     * @param Contact $contact
     * @return int
     */
    public function postContact(Contact $contact): int;


    /**
     * Ajoute un nouveau defi
     *
     * @param Defi $defi
     * @return int
     */
    public function postDefi(Defi $defi): int;
}
