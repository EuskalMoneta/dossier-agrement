<?php

namespace App\Entity;

use App\Repository\AdresseActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToMany(targetEntity: CategorieAnnuaire::class, inversedBy: 'adresseActivites')]
    private Collection $categoriesAnnuaire;

    #[ORM\ManyToMany(targetEntity: CategorieAnnuaire::class, inversedBy: 'adresseActiviteEskuz')]
    #[ORM\JoinTable(name: 'adresse_activite_categorie_annuaire_eskuz')]
    private Collection $categoriesAnnuaireEskuz;

    public function __construct()
    {
        $this->categoriesAnnuaire = new ArrayCollection();
        $this->categoriesAnnuaireEskuz = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJsonFormat(): ?string
    {
        $catAnnuaire = [];
        foreach ($this->categoriesAnnuaire as $cat){
            $catAnnuaire[]= $cat->getId();
        }

        $catAnnuaireEskuz = [];
        foreach ($this->categoriesAnnuaireEskuz as $cat){
            $catAnnuaireEskuz[]= $cat->getId();
        }

        return json_encode([
                'nom' => $this->nom,
                'adresse' => $this->adresse,
                'email' => $this->email,
                'facebook' => $this->facebook,
                'instagram' => $this->instagram,
                'telephone' => $this->telephone,
                'descriptif' => $this->descriptifActivite,
                'horaires' => $this->horaires,
                'categoriesAnnuaire' => $catAnnuaire,
                'categoriesAnnuaireEskuz' => $catAnnuaireEskuz,
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
        $this->setInstagram($contactObjet->instagram);
        $this->setFacebook($contactObjet->facebook);
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

    /**
     * @return Collection<int, CategorieAnnuaire>
     */
    public function getCategoriesAnnuaire(): Collection
    {
        return $this->categoriesAnnuaire;
    }

    public function addCategoriesAnnuaire(CategorieAnnuaire $categoriesAnnuaire): self
    {
        if (!$this->categoriesAnnuaire->contains($categoriesAnnuaire)) {
            $this->categoriesAnnuaire->add($categoriesAnnuaire);
        }

        return $this;
    }

    public function removeCategoriesAnnuaire(CategorieAnnuaire $categoriesAnnuaire): self
    {
        $this->categoriesAnnuaire->removeElement($categoriesAnnuaire);

        return $this;
    }

    public function cleanCategoriesAnnuaire(): self
    {
        $this->categoriesAnnuaire->clear();

        return $this;
    }

    /**
     * @return Collection<int, CategorieAnnuaire>
     */
    public function getCategoriesAnnuaireEskuz(): Collection
    {
        return $this->categoriesAnnuaireEskuz;
    }

    public function addCategoriesAnnuaireEskuz(CategorieAnnuaire $categoriesAnnuaireEskuz): self
    {
        if (!$this->categoriesAnnuaireEskuz->contains($categoriesAnnuaireEskuz)) {
            $this->categoriesAnnuaireEskuz->add($categoriesAnnuaireEskuz);
        }

        return $this;
    }

    public function removeCategoriesAnnuaireEskuz(CategorieAnnuaire $categoriesAnnuaireEskuz): self
    {
        $this->categoriesAnnuaireEskuz->removeElement($categoriesAnnuaireEskuz);

        return $this;
    }

    public function cleanCategoriesAnnuaireEskuz(): self
    {
        $this->categoriesAnnuaireEskuz->clear();

        return $this;
    }

}
