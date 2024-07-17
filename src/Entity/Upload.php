<?php

namespace App\Entity;

use App\Repository\UploadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: UploadRepository::class)]
class Upload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\ManyToOne(inversedBy: 'uploads')]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Vich\UploadableField(mapping: 'uploads', fileNameProperty: 'filename', size: 'fileSize')]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[ORM\OneToOne(mappedBy: 'upload', cascade: ['persist', 'remove'])]
    private ?Link $link = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?\DateTime $deleteAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file = null): static
    {
        $this->file = $file;

        return $this;
    }

    public function setFileSize(?int $imageSize): void
    {
        $this->fileSize = $imageSize;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(Link $link): static
    {
        // set the owning side of the relation if necessary
        if ($link->getUpload() !== $this) {
            $link->setUpload($this);
        }

        $this->link = $link;

        return $this;
    }

    public function getDeleteAt(): ?string
    {
        return $this->deleteAt?->format('Y-m-d H:i:s');
    }

    public function setDeleteAt(\DateTime $deleteAt): static
    {
        $this->deleteAt = $deleteAt;

        return $this;
    }

    public function calculateAndSetDeleteAt(int $fileSize): void
    {
        // Calculer le nombre de jours à ajouter
        $daysToAdd = $this->calculateDaysToAdd($fileSize);
        $deleteAt = (new \DateTime())->modify("+$daysToAdd days");

        // Vérifier que la date est valide
        if ($deleteAt !== false) {
            $this->deleteAt = $deleteAt;
        } else {
            throw new \Exception('Date de suppression invalide calculée.');
        }
    }

    private function calculateDaysToAdd(int $fileSize): int
    {
        // Convertir la taille du fichier en Go
        $fileSizeInGB = $fileSize / (1024 * 1024 * 1024);

        // Déterminer le nombre de jours à ajouter en fonction de la taille du fichier
        if ($fileSizeInGB >= 100) {
            return 3;
        }

        // Calculer les jours pour les fichiers plus petits
        return max(3, min(7, 7 - ceil($fileSizeInGB / 100 * 4)));
    }
}
