<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\StudentProfile;
use App\Form\StudentRegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/register')]
final class RegistrationController extends AbstractController
{
    #[Route('/student', name: 'app_register_student', methods: ['GET', 'POST'])]
    public function registerStudent(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): Response {
        // Form is NOT bound to a single entity (we handle manually)
        $form = $this->createForm(StudentRegistrationType::class);
        $form->handleRequest($request);

        $errors = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $email       = (string) $form->get('email')->getData();
            $plainPassword = (string) $form->get('password')->getData();
            $course      = $form->get('course')->getData();
            $yearLevel   = (int) $form->get('yearLevel')->getData();

            // Check if email already exists
            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing) {
                $errors[] = 'This email is already registered.';
            }

            if (empty($errors)) {
                // --- Create User ---
                $user = new User();
                $user->setEmail($email);
                // Student = default ROLE_USER; admin/instructor via admin panel
                $user->setRoles(['ROLE_USER']);

                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);

                // --- Create StudentProfile ---
                $profile = new StudentProfile();
                $profile->setUser($user);
                $profile->setCourse($course);
                $profile->setYearLevel($yearLevel);
                $profile->setStatus('Ongoing'); // default status for new student

                $entityManager->persist($user);
                $entityManager->persist($profile);
                $entityManager->flush();

                $this->addFlash('success', 'Your student account has been created. You can now log in.');

                // For now redirect to admin dashboard or change later to app_login
                return $this->redirectToRoute('app_admin');
            }
        }

        return $this->render('registration/register_student.html.twig', [
            'form'   => $form->createView(),
            'errors' => $errors,
        ]);
    }
}
