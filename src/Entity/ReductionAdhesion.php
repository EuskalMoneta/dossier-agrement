<?php

namespace App\Entity;

use App\Repository\ReductionAdhesionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReductionAdhesionRepository::class)]
class ReductionAdhesion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    #[Assert\LessThanOrEqual(100)]
    private ?float $pourcentageReduction = null;

    #[ORM\ManyToMany(targetEntity: DossierAgrement::class, mappedBy: 'reductionsAdhesion')]
    private Collection $dossierAgrements;

    #[ORM\Column(nullable: true)]
    private ?bool $visible = null;

    public function __toString(): string
    {
        return $this->nom;
    }

    public function __construct()
    {
        $this->dossierAgrements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPourcentageReduction(): ?float
    {
        return $this->pourcentageReduction;
    }

    public function setPourcentageReduction(?float $pourcentageReduction): self
    {
        $this->pourcentageReduction = $pourcentageReduction;

        return $this;
    }

    /**
     * @return Collection<int, DossierAgrement>
     */
    public function getDossierAgrements(): Collection
    {
        return $this->dossierAgrements;
    }

    public function addDossierAgrement(DossierAgrement $dossierAgrement): self
    {
        if (!$this->dossierAgrements->contains($dossierAgrement)) {
            $this->dossierAgrements->add($dossierAgrement);
            $dossierAgrement->addReductionsAdhesion($this);
        }

        return $this;
    }

    public function removeDossierAgrement(DossierAgrement $dossierAgrement): self
    {
        if ($this->dossierAgrements->removeElement($dossierAgrement)) {
            $dossierAgrement->removeReductionsAdhesion($this);
        }

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }
}
