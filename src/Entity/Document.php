<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $realPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated = null;

    /**
     * Unmapped property to handle file uploads
     */
    private $file;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?DossierAgrement $dossierAgrement = null;

    /**
     * Manages the copying of the file to the relevant place on the server
     */
    public function upload()
    {
        $this->storeFilenameForRemove();
        $this->removeUpload();

        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }

        $fileFutureName = date("Ymdis").$this->normalizeString($this->getFile()->getClientOriginalName());

        // move takes the target directory and target filename as params
        $this->getFile()->move(
            $this->getUploadRootDir(),
            $fileFutureName
        );

        // set the path property to the filename where you've saved the file
        $this->path = $fileFutureName;

        // clean up the file property as you won't need it anymore
        $this->setFile(null);

        $this->setUpdated(new \DateTime("now"));
    }

    public static function normalizeString ($str = '')
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = str_replace('%', '-', $str);
        $str = str_replace('+', '-', $str);

        return $str;
    }

    public function getFileNameFromType(){
        $numeroAdherent = $this->getDossierAgrement()->getCodePrestataire();

        $nomFichier = '';
        switch ($this->type){
            case 'kbis':
                $nomFichier = "-Pièce-juridique-";
                break;
            case 'identite':
                $nomFichier = "-Pièce-d-identité-";
                break;
            case 'rib':
                $nomFichier = "-RIB-";
                break;
        }

        $ext = explode('.', $this->getPath())[1];
        return $numeroAdherent.$nomFichier.$this->getId().'.'.$ext;
    }

    /**
     * Lifecycle callback to upload the file to the server
     */
    public function lifecycleFileUpload() {
        $this->upload();
    }

    /**
     * Updates the hash value to force the preUpdate and postUpdate events to fire
     */
    public function refreshUpdated() {
        $this->setUpdated(new \DateTime("now"));
    }

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../public/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/'.$this->type;
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getRealPath(): ?string
    {
        return $this->realPath;
    }

    public function setRealPath(?string $realPath): self
    {
        $this->realPath = $realPath;

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

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }


    /**
     * @ORM\PreRemove()
     */
    public function storeFilenameForRemove()
    {
        $this->realPath = $this->getAbsolutePath();
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if (isset($this->realPath)) {
            @unlink($this->realPath);
        }
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
