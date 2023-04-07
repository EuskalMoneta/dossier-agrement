<?php

namespace App\Entity;

use App\Repository\DefiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DefiRepository::class)]
class Defi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column(nullable: true)]
    private ?bool $etat = null;

    #[ORM\ManyToOne(inversedBy: 'defis')]
    private ?DossierAgrement $dossierAgrement = null;

    public function __toString(): string
    {
        return $this->getLabelDefiCRM().' : '.$this->valeur.' ('.$this->etat.')';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabelDefiCRM(){
        if($this->type == 'professionnel'){
            return 'Trois prestataires du réseau';
        } elseif ($this->type == 'accueilEuskara'){
            return 'Accueil en euskara';
        } elseif ($this->type == 'promotionEuskara'){
            return 'Affichage en euskara';
        }elseif ($this->type == 'produit'){
            return 'Trois produits locaux';
        } elseif ($this->type == 'reutiliser'){
            return 'Réutiliser à titre personnel';
        } else {
            return 'defi';
        }
    }

    public function getEtatReadable(){
        if($this->etat){
            return "déjà réalisé";
        } else {
            return "à réaliser";
        }
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(?string $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function isEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(?bool $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getDossierAgrement(): ?DossierAgrement
    {
        return $this->dossierAgrement;
    }

    public function setDossierAgrement(?DossierAgrement $dossierAgrement): self
    {
        $this->dossierAgrement = $dossierAgrement;

        return $this;
    }
}
