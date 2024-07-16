<?php

namespace App\Service;

use App\Repository\UploadRepository;
use Doctrine\ORM\EntityManagerInterface;

class FileDeletionService
{
    private $uploadRepository;
    private $entityManager;

    public function __construct(UploadRepository $uploadRepository, EntityManagerInterface $entityManager)
    {
        $this->uploadRepository = $uploadRepository;
        $this->entityManager = $entityManager;
    }

    public function deleteExpiredFiles(): void
    {
        $uploads = $this->uploadRepository->findAll();
        $currentDate = new \DateTime();

        foreach ($uploads as $upload) {
            $deleteAt = $upload->getDeleteAt();

            if ($deleteAt !== null && $currentDate > $deleteAt) {
                $this->entityManager->remove($upload);
            }
        }

        $this->entityManager->flush();
    }
}