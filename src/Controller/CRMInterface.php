<?php
namespace App\Controller;

use App\Entity\DossierAgrement;

interface CRMInterface
{

    /**
     * Récupère un tableau des catégorie de l'annuaire
     * [
     *   ['idExterne' => $val,
     *    'libelle' => $val
     *   ],
     *   ...
     * ]
     */
    public function getCategoriesAnnuaire():array;

    /**
     * Récupère un tableau des catégorie de l'annuaire Eskuz esku
     */
    public function getCategoriesEskuzEsku():array;

    /**
     * Rechercher un professionnel
     * retourne un tableau de résultats
     */
    public function searchProfessionnel($term):array;

    /**
     * Mettre à jour un tier existant
     */
    public function updateTier($id):bool;
}