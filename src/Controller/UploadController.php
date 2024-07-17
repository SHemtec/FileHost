<?php

namespace App\Controller;

use App\Entity\Upload;
use App\Entity\User;
use App\Form\UploadType;
use App\Repository\UploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/{username}/upload')]
//verifie que l'utilisateur a le role uploader ou admin
#[IsGranted('ROLE_UPLOADER')]
class UploadController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_upload_index', methods: ['GET'])]
    public function index(UploadRepository $uploadRepository, ): Response
    {
        // Récupère l'utilisateur connecté
        $user = $this->security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupère le nom d'utilisateur
        $username = $user->getUsername();

        // Récupère les uploads de l'utilisateur
        $uploads = $uploadRepository->findBy(['user' => $user]);

        //recupere les liens des uploads si ils en ont
        foreach ($uploads as $upload) {
            $link = $upload->getLink();
            if ($link) {
                $links[] = $link;
            }
        }

        return $this->render('upload/index.html.twig', [
            'uploads' => $uploads,
            'username' => $username,
        ]);
    }

    #[Route('/add', name: 'app_upload_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupère l'utilisateur connecté
        $user = $this->security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupère le nom d'utilisateur
        $username = $user->getUsername();

        $upload = new Upload();
        $form = $this->createForm(UploadType::class, $upload);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userId = $user->getId();
            $UserInstance = $entityManager->getRepository(User::class)->find($userId);
            $upload->setUser($UserInstance);

            // Increment the upload count
            $UserInstance->incrementUploadCount();

            $upload->setCreatedAt(new \DateTimeImmutable());
            //recupere la taille, sur la clé de tableau 'file' et l'objet size du tableau

            $uploadedFile = $request->files->get('upload')['file']['file'];

            if ($uploadedFile) {
                $upload->calculateAndSetDeleteAt($uploadedFile->getSize());

            }

            $entityManager->persist($upload);
            $entityManager->flush();

            return $this->redirectToRoute('app_upload_index', ['username' => $username], Response::HTTP_SEE_OTHER);
        }

        return $this->render('upload/new.html.twig', [
            'upload' => $upload,
            'form' => $form,
            'username' => $username,
        ]);
    }

    #[Route('/{id}', name: 'app_upload_delete', methods: ['POST'])]
    public function delete(Request $request, Upload $upload, EntityManagerInterface $entityManager): Response
    {
        // Récupère l'utilisateur connecté
        $user = $this->security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupère le nom d'utilisateur
        $username = $user->getUsername();



        if ($this->isCsrfTokenValid('delete'.$upload->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($upload);
            $entityManager->flush();
            return $this->redirectToRoute('app_upload_index', ['username' => $username], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_upload_index', ['username' => $username], Response::HTTP_SEE_OTHER);
    }

    /////test


    #[Route('/upload-chunk', name: 'upload_chunk', methods: ['POST'])]
    public function uploadChunk(Request $request): Response
    {
        $chunk = $request->files->get('file');
        $chunkIndex = $request->request->get('chunkIndex');
        $totalChunks = $request->request->get('totalChunks');
        $fileName = $request->request->get('fileName');

        $uploadDir = $this->getParameter('kernel.project_dir') . '/uploads';
        $chunkDir = $uploadDir . '/' . $fileName . '_chunks';

        if (!is_dir($chunkDir)) {
            mkdir($chunkDir, 0777, true);
        }

        $chunk->move($chunkDir, $chunkIndex);

        if ($chunkIndex == $totalChunks - 1) {
            $this->assembleChunks($chunkDir, $uploadDir . '/' . $fileName);
            $this->cleanupChunks($chunkDir);
        }

        return new Response('Chunk uploaded successfully.', Response::HTTP_OK);
    }

    private function assembleChunks($chunkDir, $finalFilePath)
    {
        $files = scandir($chunkDir);
        natsort($files);

        $finalFile = fopen($finalFilePath, 'wb');
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $chunk = fopen($chunkDir . '/' . $file, 'rb');
            stream_copy_to_stream($chunk, $finalFile);
            fclose($chunk);
        }
        fclose($finalFile);
    }

    private function cleanupChunks($chunkDir)
    {
        $files = scandir($chunkDir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            unlink($chunkDir . '/' . $file);
        }
        rmdir($chunkDir);
    }
}
