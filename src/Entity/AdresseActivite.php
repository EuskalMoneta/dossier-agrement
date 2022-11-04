<?php

namespace App\Entity;

use App\Repository\AdresseActiviteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdresseActiviteRepository::class)]
class AdresseActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $horaires = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptifActivite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $autresLieux = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorieAnnuaire = null;

    #[ORM\Column(nullable: true)]
    private ?bool $guideVEE = null;

    #[ORM\ManyToOne(inversedBy: 'adresseActivites')]
    private ?DossierAgrement $dossier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJsonFormat(): ?string
    {
        return json_encode([
                'nom' => $this->nom,
                'adresse' => $this->adresse,
                'email' => $this->email,
                'telephone' => $this->telephone,
                'descriptif' => $this->descriptifActivite,
                'horaires' => $this->horaires,
                'autresLieux' => $this->autresLieux,
                'guide' => $this->guideVEE,
                'id' => $this->id,
            ]
        );
    }

    public function updateFormJsonObject($contactObjet): self
    {
        $this->setNom($contactObjet->nom);
        $this->setAdresse($contactObjet->adresse);
        $this->setEmail($contactObjet->email);
        $this->setTelephone($contactObjet->telephone);
        $this->setDescriptifActivite($contactObjet->descriptif);
        $this->setHoraires($contactObjet->horaires);
        $this->setAutresLieux($contactObjet->autresLieux);
        $this->setGuideVEE($contactObjet->guide);

        return $this;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getHoraires(): ?string
    {
        return $this->horaires;
    }

    public function setHoraires(?string $horaires): self
    {
        $this->horaires = $horaires;

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getDescriptifActivite(): ?string
    {
        return $this->descriptifActivite;
    }

    public function setDescriptifActivite(?string $descriptifActivite): self
    {
        $this->descriptifActivite = $descriptifActivite;

        return $this;
    }

    public function getAutresLieux(): ?string
    {
        return $this->autresLieux;
    }

    public function setAutresLieux(?string $autresLieux): self
    {
        $this->autresLieux = $autresLieux;

        return $this;
    }

    public function getCategorieAnnuaire(): ?string
    {
        return $this->categorieAnnuaire;
    }

    public function setCategorieAnnuaire(?string $categorieAnnuaire): self
    {
        $this->categorieAnnuaire = $categorieAnnuaire;

        return $this;
    }

    public function isGuideVEE(): ?bool
    {
        return $this->guideVEE;
    }

    public function setGuideVEE(?bool $guideVEE): self
    {
        $this->guideVEE = $guideVEE;

        return $this;
    }

    public function getDossier(): ?DossierAgrement
    {
        return $this->dossier;
    }

    public function setDossier(?DossierAgrement $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }
}
