<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(): Response
    {
        return $this->render('contact/index.html.twig', [
            'controller_name' => 'ContactController',
        ]);
    }

    #[Route('/sendContactForm', name: 'app_send_contact_form')]
    public function sendContactForm(Request $request, EmailService $emailService) {
        $subject = $request->request->get('subject');
        $email = $request->request->get('email');
        $message = $request->request->get('message');

        if ($emailService->sendContactEmail($subject, $email, $message)) {
            $this->addFlash('success', 'Votre message a bien été envoyé !');
            return $this->redirectToRoute('app_index');
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre message.');
            return $this->redirectToRoute('app_contact');
        }
    }
}
