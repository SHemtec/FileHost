<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegisterController extends AbstractController
{
    public function __construct(private EmailService $emailService, LoggerInterface $logger)
    {
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    #[Route('/register', name: 'app_register')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifiez si l'utilisateur existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            // Remplacez 'username' par 'email' si vous utilisez l'email comme identifiant unique

            if ($existingUser) {
                $this->addFlash('error', 'Un compte avec ce nom d\'utilisateur existe déjà.');
                return $this->redirectToRoute('app_register');
            }

            $user->setToken(bin2hex(random_bytes(32)));
            // Le reste du code pour persister et envoyer l'email...
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setToken(bin2hex(random_bytes(32)));
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $entityManager->persist($user);
            if ($this->emailService->sendRegistrationNotification($user)) {
                $this->addFlash('success', 'Votre demande d\'inscription a bien été envoyée !');
                $entityManager->flush();
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre demande d\'inscription');
                $this->logger->error('Une erreur est survenue lors de l\'envoi de votre demande d\'inscription pour l\'utilisateur ' . $user->getUsername());
            }

            return $this->redirectToRoute('app_index');
        }

        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/approve/{token}', name: 'app_approve_user')]
    public function approveRegistration($token, EntityManagerInterface $entityManager) {
        $user = $entityManager->getRepository(User::class)->findOneBy(['token' => $token]);
        if (!$user) {
            // Gérer l'erreur si l'utilisateur n'est pas trouvé
            $this->addFlash('error', 'L\'utilisateur n\'a pas été trouvé.');
            return $this->redirectToRoute('app_index');
        }

        //verifie si l'utilisateur est pas deja validé
        if ($user->getIsValid()) {
            $this->addFlash('alert', 'L\'utilisateur a déjà été approuvé.');
            return $this->redirectToRoute('app_index');
        }

        $user->setIsValid(true);
        $user->setRoles(['ROLE_UPLOADER']);
        $entityManager->flush();

        $this->addFlash('success', 'Le compte de l\'utilisateur a été approuvé !');
        return $this->redirectToRoute('app_index');
    }


}
