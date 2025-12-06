<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em): Response
    {
        $name = trim((string) $request->request->get('name'));
        $email = trim((string) $request->request->get('email'));
        $subject = trim((string) $request->request->get('subject'));
        $messageText = trim((string) $request->request->get('message'));

        // Simple required validation
        if ($name === '' || $email === '' || $messageText === '') {
            $this->addFlash('error', 'Please fill in your name, email, and message.');
            return $this->redirectToRoute('home', ['_fragment' => 'contact']);
        }

        $contact = new ContactMessage();
        $contact
            ->setName($name)
            ->setEmail($email)
            ->setSubject($subject !== '' ? $subject : null)
            ->setMessage($messageText);

        $em->persist($contact);
        $em->flush();

        $this->addFlash('success', 'Your message has been sent. Thank you!');
        return $this->redirectToRoute('home', ['_fragment' => 'contact']);
    }
}
