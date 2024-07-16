<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager, Request $request): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/login/check', name: 'app_login_check')]
    public function loginCheck(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, Request $request): Response
    {
        $email = $request->request->get('_username');

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Si l'utilisateur n'existe pas
        if (!$user) {
            $this->addFlash('error', 'Cet utilisateur n\'éxiste pas.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier le mot de passe
        $submittedPassword = $request->request->get('_password'); // Assurez-vous que le nom du champ correspond à celui de votre formulaire
        if (!password_verify($submittedPassword, $user->getPassword())) {
            $this->addFlash('error', 'Mot de passe incorrect.');
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur existe et le mot de passe est correct, vérifie si l'utilisateur est valide
        if ($user->isValid()) {
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);
            return $this->redirect('/');
        }

        // Si les conditions ne sont pas remplies, redirige vers la page de déconnexion ou une page d'erreur
        $this->addFlash('error', 'Votre compte n\'a pas (encore) été validé, veuillez contacter l\'administrateur.');
        return $this->redirect('/login');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
