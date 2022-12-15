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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbSalarie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeCotisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fraisDeDossier = null;

    #[ORM\Column(nullable: true)]
    private ?bool $compteNumeriqueBool = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $compteNumerique = null;

    #[ORM\Column(nullable: true)]
    private ?bool $terminalPaiementBool = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $terminalPaiement = null;

    #[ORM\Column(nullable: true)]
    private ?bool $euskopayBool = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $euskopay = null;

    #[ORM\Column(nullable: true)]
    private ?bool $paiementViaEuskopay = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siren = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bic = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomSignature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenomSignature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephoneSignature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fraisDeDossierRecu = null;

    #[ORM\OneToMany(mappedBy: 'dossierAgrement', targetEntity: Contact::class)]
    private Collection $contacts;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AdresseActivite::class)]
    private Collection $adresseActivites;

    #[ORM\OneToMany(mappedBy: 'dossierAgrement', targetEntity: Defi::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $defis;

    #[ORM\OneToMany(mappedBy: 'dossierAgrement', targetEntity: Fournisseur::class)]
    private Collection $fournisseurs;

    #[ORM\ManyToMany(targetEntity: ReductionAdhesion::class, inversedBy: 'dossierAgrements')]
    private Collection $reductionsAdhesion;

    #[ORM\OneToMany(mappedBy: 'dossierAgrement', targetEntity: Document::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sepaBase64 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etat = "nouveau";

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAgrement = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codePrestataire = null;

    #[ORM\Column(nullable: true)]
    private ?int $idExterne = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statutChargesDeveloppement = 'en cours';

    #[ORM\Column(nullable: true)]
    private ?bool $recevoirNewsletter = null;

    #[ORM\ManyToOne(inversedBy: 'dossierAgrements')]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idAdherent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autocollantVitrine = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autocollantPanneau = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeAutocollant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;


    public function __toString(): string
    {
        return $this->libelle;
    }


    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->adresseActivites = new ArrayCollection();
        $this->defis = new ArrayCollection();
        $this->fournisseurs = new ArrayCollection();

        $defi = new Defi();
        $defi->setType('reutiliser');
        $this->addDefi($defi);

        $defi = new Defi();
        $defi->setType('promotionEuskara');
        $this->addDefi($defi);

        $defi = new Defi();
        $defi->setType('accueilEuskara');
        $this->addDefi($defi);

        $defi = new Defi();
        $defi->setType('enargia');
        $this->addDefi($defi);

        $defi = new Defi();
        $defi->setType('paysBasqueAuCoeur');
        $this->addDefi($defi);

        $defi = new Defi();
        $defi->setType('lantegiak');
        $this->addDefi($defi);
        $this->reductionsAdhesion = new ArrayCollection();
        $this->documents = new ArrayCollection();

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

    /**
     * @return Collection<int, Defi>
     */
    public function getDefis(): Collection
    {
        return $this->defis;
    }

    public function addDefi(Defi $defi): self
    {
        if (!$this->defis->contains($defi)) {
            $this->defis->add($defi);
            $defi->setDossierAgrement($this);
        }

        return $this;
    }

    public function removeDefi(Defi $defi): self
    {
        if ($this->defis->removeElement($defi)) {
            // set the owning side to null (unless already changed)
            if ($defi->getDossierAgrement() === $this) {
                $defi->setDossierAgrement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Fournisseur>
     */
    public function getFournisseurs(): Collection
    {
        return $this->fournisseurs;
    }

    public function addFournisseur(Fournisseur $fournisseur): self
    {
        if (!$this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs->add($fournisseur);
            $fournisseur->setDossierAgrement($this);
        }

        return $this;
    }

    public function removeFournisseur(Fournisseur $fournisseur): self
    {
        if ($this->fournisseurs->removeElement($fournisseur)) {
            // set the owning side to null (unless already changed)
            if ($fournisseur->getDossierAgrement() === $this) {
                $fournisseur->setDossierAgrement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ReductionAdhesion>
     */
    public function getReductionsAdhesion(): Collection
    {
        return $this->reductionsAdhesion;
    }

    public function addReductionsAdhesion(ReductionAdhesion $reductionsAdhesion): self
    {
        if (!$this->reductionsAdhesion->contains($reductionsAdhesion)) {
            $this->reductionsAdhesion->add($reductionsAdhesion);
        }

        return $this;
    }

    public function removeReductionsAdhesion(ReductionAdhesion $reductionsAdhesion): self
    {
        $this->reductionsAdhesion->removeElement($reductionsAdhesion);

        return $this;
    }

    public function cleanReductionsAdhesion(): self
    {
        $this->reductionsAdhesion->clear();

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

    public function getNbSalarie(): ?int
    {
        return $this->nbSalarie;
    }

    public function setNbSalarie(?int $nbSalarie): self
    {
        $this->nbSalarie = $nbSalarie;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getTypeCotisation(): ?string
    {
        return $this->typeCotisation;
    }

    public function setTypeCotisation(?string $typeCotisation): self
    {
        $this->typeCotisation = $typeCotisation;

        return $this;
    }

    public function getFraisDeDossier(): ?string
    {
        return $this->fraisDeDossier;
    }

    public function setFraisDeDossier(?string $fraisDeDossier): self
    {
        $this->fraisDeDossier = $fraisDeDossier;

        return $this;
    }

    public function isCompteNumeriqueBool(): ?bool
    {
        return $this->compteNumeriqueBool;
    }

    public function setCompteNumeriqueBool(?bool $compteNumeriqueBool): self
    {
        $this->compteNumeriqueBool = $compteNumeriqueBool;

        return $this;
    }

    public function getCompteNumerique(): ?string
    {
        return $this->compteNumerique;
    }

    public function setCompteNumerique(?string $compteNumerique): self
    {
        $this->compteNumerique = $compteNumerique;

        return $this;
    }

    public function isTerminalPaiementBool(): ?bool
    {
        return $this->terminalPaiementBool;
    }

    public function setTerminalPaiementBool(?bool $terminalPaiementBool): self
    {
        $this->terminalPaiementBool = $terminalPaiementBool;

        return $this;
    }

    public function getTerminalPaiement(): ?string
    {
        return $this->terminalPaiement;
    }

    public function setTerminalPaiement(?string $terminalPaiement): self
    {
        $this->terminalPaiement = $terminalPaiement;

        return $this;
    }

    public function isEuskopayBool(): ?bool
    {
        return $this->euskopayBool;
    }

    public function setEuskopayBool(?bool $euskopayBool): self
    {
        $this->euskopayBool = $euskopayBool;

        return $this;
    }

    public function getEuskopay(): ?string
    {
        return $this->euskopay;
    }

    public function setEuskopay(?string $euskopay): self
    {
        $this->euskopay = $euskopay;

        return $this;
    }

    public function isPaiementViaEuskopay(): ?bool
    {
        return $this->paiementViaEuskopay;
    }

    public function setPaiementViaEuskopay(?bool $paiementViaEuskopay): self
    {
        $this->paiementViaEuskopay = $paiementViaEuskopay;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setDossierAgrement($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getDossierAgrement() === $this) {
                $document->setDossierAgrement(null);
            }
        }

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): self
    {
        $this->siren = $siren;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function getNomSignature(): ?string
    {
        return $this->nomSignature;
    }

    public function setNomSignature(?string $nomSignature): self
    {
        $this->nomSignature = $nomSignature;

        return $this;
    }

    public function getPrenomSignature(): ?string
    {
        return $this->prenomSignature;
    }

    public function setPrenomSignature(?string $prenomSignature): self
    {
        $this->prenomSignature = $prenomSignature;

        return $this;
    }

    public function getTelephoneSignature(): ?string
    {
        return $this->telephoneSignature;
    }

    public function setTelephoneSignature(?string $telephoneSignature): self
    {
        $this->telephoneSignature = $telephoneSignature;

        return $this;
    }

    public function getFraisDeDossierRecu(): ?string
    {
        return $this->fraisDeDossierRecu;
    }

    public function setFraisDeDossierRecu(?string $fraisDeDossierRecu): self
    {
        $this->fraisDeDossierRecu = $fraisDeDossierRecu;

        return $this;
    }

    public function getSepaBase64(): ?string
    {
        return $this->sepaBase64;
    }

    public function setSepaBase64(?string $sepaBase64): self
    {
        $this->sepaBase64 = $sepaBase64;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getDateAgrement(): ?\DateTimeInterface
    {
        return $this->dateAgrement;
    }

    public function setDateAgrement(?\DateTimeInterface $dateAgrement): self
    {
        $this->dateAgrement = $dateAgrement;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getCodePrestataire(): ?string
    {
        return $this->codePrestataire;
    }

    public function setCodePrestataire(?string $codePrestataire): self
    {
        $this->codePrestataire = $codePrestataire;

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

    public function getStatutChargesDeveloppement(): ?string
    {
        return $this->statutChargesDeveloppement;
    }

    public function setStatutChargesDeveloppement(?string $statutChargesDeveloppement): self
    {
        $this->statutChargesDeveloppement = $statutChargesDeveloppement;

        return $this;
    }

    public function isRecevoirNewsletter(): ?bool
    {
        return $this->recevoirNewsletter;
    }

    public function setRecevoirNewsletter(?bool $recevoirNewsletter): self
    {
        $this->recevoirNewsletter = $recevoirNewsletter;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getIdAdherent(): ?string
    {
        return $this->idAdherent;
    }

    public function setIdAdherent(?string $idAdherent): self
    {
        $this->idAdherent = $idAdherent;

        return $this;
    }

    public function getAutocollantVitrine(): ?string
    {
        return $this->autocollantVitrine;
    }

    public function setAutocollantVitrine(?string $autocollantVitrine): self
    {
        $this->autocollantVitrine = $autocollantVitrine;

        return $this;
    }

    public function getAutocollantPanneau(): ?string
    {
        return $this->autocollantPanneau;
    }

    public function setAutocollantPanneau(?string $autocollantPanneau): self
    {
        $this->autocollantPanneau = $autocollantPanneau;

        return $this;
    }

    public function getTypeAutocollant(): ?string
    {
        return $this->typeAutocollant;
    }

    public function setTypeAutocollant(?string $typeAutocollant): self
    {
        $this->typeAutocollant = $typeAutocollant;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): self
    {
        $this->siteWeb = $siteWeb;

        return $this;
    }


}
