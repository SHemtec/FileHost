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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/{username}/upload')]
#[IsGranted('ROLE_UPLOADER')]
class UploadController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_upload_index', methods: ['GET'])]
    public function index(UploadRepository $uploadRepository): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $username = $user->getUsername();
        $uploads = $uploadRepository->findBy(['user' => $user]);

        return $this->render('upload/index.html.twig', [
            'uploads' => $uploads,
            'username' => $username,
        ]);
    }

    #[Route('/add', name: 'app_upload_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $username = $user->getUsername();
        $upload = new Upload();
        $form = $this->createForm(UploadType::class, $upload);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userId = $user->getId();
            $UserInstance = $entityManager->getRepository(User::class)->find($userId);
            $upload->setUser($UserInstance);
            $UserInstance->incrementUploadCount();
            $upload->setCreatedAt(new \DateTimeImmutable());

            $uploadedFile = $request->files->get('upload')['file']['file'];

            if ($uploadedFile) {
                $upload->calculateAndSetDeleteAt($uploadedFile->getSize());
            }

            $entityManager->persist($upload);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => 'Le fichier a été envoyé avec succés'], 200);
            }

            return $this->redirectToRoute('app_upload_index', ['username' => $username], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => 'L\'envoi du fichier a échoué'], 400);
        }

        return $this->render('upload/new.html.twig', [
            'upload' => $upload,
            'form' => $form->createView(),
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
}
