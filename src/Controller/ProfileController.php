<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UploadRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{

    public function __construct(UserPasswordHasherInterface $passwordHasher, UploadRepository $uploadRepository)
    {
        $this->passwordHasher = $passwordHasher;
        $this->uploadRepository = $uploadRepository;
    }

    #[\Symfony\Component\Routing\Annotation\Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {
        $currentUploadCount = $this->uploadRepository->countCurrentUploadsByUser($user->getId());

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'uploads' => $currentUploadCount,
        ]);
    }

    #[Route('/{username}/modification', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, string $username, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/profile/delete/{token}', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, UserRepository $userRepository, string $token): Response
    {
        $user = $userRepository->findOneBy(['token' => $token]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            // Enregistrer l'ID de l'utilisateur à supprimer dans la session
            $session = $request->getSession();
            $session->set('user_to_delete', $user->getId());

            $this->addFlash('success', ' RD01 OK.');
            return $this->redirectToRoute('app_profile_delete_user');
        }

        $this->addFlash('error', 'Une erreur s\'est produite lors de la suppression de votre compte DU01.');
        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/profile/delete_user', name: 'app_profile_delete_user')]
    public function deleteUserAfterLogout(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_to_delete');

        if ($userId) {
            $user = $userRepository->find($userId);
            if ($user) {
                $entityManager->remove($user);
                $entityManager->flush();
                $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
                return $this->redirectToRoute('app_index');
            }
            // Supprimez l'ID de l'utilisateur de la session après suppression
            $session->remove('user_to_delete');
        }

        $this->addFlash('error', 'Une erreur s\'est produite lors de la suppression de votre compte. DU02');
        return $this->redirectToRoute('app_index');
    }
}
