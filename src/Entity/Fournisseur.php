<?php

namespace App\Entity;

use App\Repository\FournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomEntreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaires = null;

    #[ORM\ManyToOne(inversedBy: 'fournisseurs')]
    private ?DossierAgrement $dossierAgrement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activite = null;

    public function getJsonFormat(): ?string
    {
        return json_encode([
                'entreprise' => $this->nomEntreprise,
                'nom' => $this->nom,
                'prenom' => $this->prenom,
                'activite' => $this->activite,
                'adresse' => $this->adresse,
                'email' => $this->email,
                'telephone' => $this->telephone,
                'commentaires' => $this->commentaires,
                'id' => $this->id,
            ]
        );
    }

    public function updateFormJsonObject($fournisseurObjet): self
    {
        $this->setNomEntreprise($fournisseurObjet->entreprise);
        $this->setPrenom($fournisseurObjet->prenom);
        $this->setNom($fournisseurObjet->nom);
        $this->setActivite($fournisseurObjet->activite);
        $this->setAdresse($fournisseurObjet->adresse);
        $this->setEmail($fournisseurObjet->email);
        $this->setTelephone($fournisseurObjet->telephone);
        $this->setCommentaires($fournisseurObjet->commentaires);

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEntreprise(): ?string
    {
        return $this->nomEntreprise;
    }

    public function setNomEntreprise(?string $nomEntreprise): self
    {
        $this->nomEntreprise = $nomEntreprise;

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

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

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    public function setCommentaires(?string $commentaires): self
    {
        $this->commentaires = $commentaires;

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

    public function getActivite(): ?string
    {
        return $this->activite;
    }

    public function setActivite(?string $activite): self
    {
        $this->activite = $activite;

        return $this;
    }
}
