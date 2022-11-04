<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fonction = null;

    #[ORM\Column(nullable: true)]
    private ?bool $interlocuteur = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    private ?DossierAgrement $dossierAgrement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJsonFormat(): ?string
    {
        return json_encode([
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'interlocuteur' => $this->interlocuteur,
            'fonction' => $this->fonction,
            'id' => $this->id,
            ]
        );
    }

    public function updateFormJsonObject($contactObjet): self
    {
        $this->setNom($contactObjet->nom);
        $this->setPrenom($contactObjet->prenom);
        $this->setEmail($contactObjet->email);
        $this->setTelephone($contactObjet->telephone);
        $this->setFonction($contactObjet->fonction);
        $this->setInterlocuteur($contactObjet->interlocuteur);

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

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): self
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function isInterlocuteur(): ?bool
    {
        return $this->interlocuteur;
    }

    public function setInterlocuteur(?bool $interlocuteur): self
    {
        $this->interlocuteur = $interlocuteur;

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
