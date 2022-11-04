<?php

namespace App\Entity;

use App\Repository\DossierAgrementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierAgrementRepository::class)]
class DossierAgrement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $denominationCommerciale = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formeJuridique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adressePrincipale = null;

    #[ORM\OneToMany(mappedBy: 'dossierAgrement', targetEntity: Contact::class)]
    private Collection $contacts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailPrincipal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomDirigeant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenomDirigeant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephoneDirigeant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailDirigeant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fonctionDirigeant = null;

    #[ORM\Column(nullable: true)]
    private ?bool $interlocuteurDirigeant = null;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AdresseActivite::class)]
    private Collection $adresseActivites;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->adresseActivites = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDenominationCommerciale(): ?string
    {
        return $this->denominationCommerciale;
    }

    public function setDenominationCommerciale(?string $denominationCommerciale): self
    {
        $this->denominationCommerciale = $denominationCommerciale;

        return $this;
    }

    public function getFormeJuridique(): ?string
    {
        return $this->formeJuridique;
    }

    public function setFormeJuridique(?string $formeJuridique): self
    {
        $this->formeJuridique = $formeJuridique;

        return $this;
    }

    public function getAdressePrincipale(): ?string
    {
        return $this->adressePrincipale;
    }

    public function setAdressePrincipale(?string $adressePrincipale): self
    {
        $this->adressePrincipale = $adressePrincipale;

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setDossierAgrement($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getDossierAgrement() === $this) {
                $contact->setDossierAgrement(null);
            }
        }

        return $this;
    }

    public function getEmailPrincipal(): ?string
    {
        return $this->emailPrincipal;
    }

    public function setEmailPrincipal(?string $emailPrincipal): self
    {
        $this->emailPrincipal = $emailPrincipal;

        return $this;
    }

    public function getNomDirigeant(): ?string
    {
        return $this->nomDirigeant;
    }

    public function setNomDirigeant(?string $nomDirigeant): self
    {
        $this->nomDirigeant = $nomDirigeant;

        return $this;
    }

    public function getPrenomDirigeant(): ?string
    {
        return $this->prenomDirigeant;
    }

    public function setPrenomDirigeant(?string $prenomDirigeant): self
    {
        $this->prenomDirigeant = $prenomDirigeant;

        return $this;
    }

    public function getTelephoneDirigeant(): ?string
    {
        return $this->telephoneDirigeant;
    }

    public function setTelephoneDirigeant(?string $telephoneDirigeant): self
    {
        $this->telephoneDirigeant = $telephoneDirigeant;

        return $this;
    }

    public function getEmailDirigeant(): ?string
    {
        return $this->emailDirigeant;
    }

    public function setEmailDirigeant(?string $emailDirigeant): self
    {
        $this->emailDirigeant = $emailDirigeant;

        return $this;
    }

    public function getFonctionDirigeant(): ?string
    {
        return $this->fonctionDirigeant;
    }

    public function setFonctionDirigeant(?string $fonctionDirigeant): self
    {
        $this->fonctionDirigeant = $fonctionDirigeant;

        return $this;
    }

    public function isInterlocuteurDirigeant(): ?bool
    {
        return $this->interlocuteurDirigeant;
    }

    public function setInterlocuteurDirigeant(?bool $interlocuteurDirigeant): self
    {
        $this->interlocuteurDirigeant = $interlocuteurDirigeant;

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
            $adresseActivite->setDossier($this);
        }

        return $this;
    }

    public function removeAdresseActivite(AdresseActivite $adresseActivite): self
    {
        if ($this->adresseActivites->removeElement($adresseActivite)) {
            // set the owning side to null (unless already changed)
            if ($adresseActivite->getDossier() === $this) {
                $adresseActivite->setDossier(null);
            }
        }

        return $this;
    }
}
