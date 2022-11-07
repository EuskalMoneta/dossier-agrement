<?php

namespace App\Entity;

use App\Repository\CategorieAnnuaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieAnnuaireRepository::class)]
class CategorieAnnuaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(nullable: true)]
    private ?int $idExterne = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = 'eusko';

    #[ORM\ManyToMany(targetEntity: AdresseActivite::class, mappedBy: 'categoriesAnnuaire')]
    private Collection $adresseActivites;

    public function __construct()
    {
        $this->adresseActivites = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->libelle;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getIdExterne(): ?int
    {
        return $this->idExterne;
    }

    public function setIdExterne(?int $idExterne): self
    {
        $this->idExterne = $idExterne;

        return $this;
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

    /**
     * @return Collection<int, AdresseActivite>
     */
    public function getAdresseActivites(): Collection
    {
        return $this->adresseActivites;
    }

    public function addAdresseActivite(AdresseActivite $adresseActivite): self
    {
        if (!$this->adresseActivites->contains($adresseActivite)) {
            $this->adresseActivites->add($adresseActivite);
            $adresseActivite->addCategoriesAnnuaire($this);
        }

        return $this;
    }

    public function removeAdresseActivite(AdresseActivite $adresseActivite): self
    {
        if ($this->adresseActivites->removeElement($adresseActivite)) {
            $adresseActivite->removeCategoriesAnnuaire($this);
        }

        return $this;
    }

}
